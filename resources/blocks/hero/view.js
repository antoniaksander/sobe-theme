// Hero WebGL aurora cursor effect.
// Self-initializes on every [data-hero-webgl="true"] section.
// No dependencies — raw WebGL API only.

const VERT = `
  attribute vec2 a_pos;
  varying vec2 v_uv;
  void main() {
    v_uv = a_pos * 0.5 + 0.5;
    gl_Position = vec4(a_pos, 0.0, 1.0);
  }
`;

// Aurora: two crossing sine/cosine wave fields modulated by mouse UV + time.
// mix-blend-mode:screen on the canvas means the glow only brightens, never darkens.
const FRAG = `
  precision mediump float;
  uniform float u_time;
  uniform vec2  u_mouse;
  varying vec2  v_uv;

  void main() {
    vec2 uv = v_uv;

    float w1 = sin(uv.x * 6.0 + u_time * 0.7 + u_mouse.x * 3.14159)
             * cos(uv.y * 5.0 + u_time * 0.5 + u_mouse.y * 2.5);

    float w2 = sin(uv.x * 4.0 - u_time * 0.4 + u_mouse.y * 2.0)
             * cos(uv.y * 7.0 + u_time * 0.6 - u_mouse.x * 3.0);

    float b  = (w1 + w2) * 0.25 + 0.5;

    float r = 0.3 + 0.4 * sin(b * 3.14 + u_time * 0.2);
    float g = 0.1 + 0.3 * sin(b * 3.14 + u_time * 0.3 + 1.0);
    float bl = 0.7 + 0.3 * cos(b * 3.14 - u_time * 0.25 + 2.0);
    float a  = 0.38 * b;

    gl_FragColor = vec4(r * a, g * a, bl * a, a);
  }
`;

function compile(gl, type, src) {
  const s = gl.createShader(type);
  gl.shaderSource(s, src);
  gl.compileShader(s);
  return s;
}

function initHero(section) {
  const canvas = document.createElement('canvas');
  canvas.className = 'hero__webgl';
  canvas.setAttribute('aria-hidden', 'true');
  Object.assign(canvas.style, {
    position: 'absolute',
    inset: '0',
    width: '100%',
    height: '100%',
    pointerEvents: 'none',
    mixBlendMode: 'screen',
    zIndex: '1',
  });
  section.appendChild(canvas);

  const gl =
    canvas.getContext('webgl', { alpha: true }) ||
    canvas.getContext('experimental-webgl', { alpha: true });
  if (!gl) return;

  const prog = gl.createProgram();
  gl.attachShader(prog, compile(gl, gl.VERTEX_SHADER, VERT));
  gl.attachShader(prog, compile(gl, gl.FRAGMENT_SHADER, FRAG));
  gl.linkProgram(prog);
  gl.useProgram(prog);

  const buf = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buf);
  gl.bufferData(
    gl.ARRAY_BUFFER,
    new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]),
    gl.STATIC_DRAW,
  );

  const aPos = gl.getAttribLocation(prog, 'a_pos');
  gl.enableVertexAttribArray(aPos);
  gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

  const uTime = gl.getUniformLocation(prog, 'u_time');
  const uMouse = gl.getUniformLocation(prog, 'u_mouse');

  gl.enable(gl.BLEND);
  gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
  gl.clearColor(0, 0, 0, 0);

  const mouse = { x: 0.5, y: 0.5 };
  const target = { x: 0.5, y: 0.5 };
  let raf = null;

  function resize() {
    canvas.width = section.offsetWidth;
    canvas.height = section.offsetHeight;
    gl.viewport(0, 0, canvas.width, canvas.height);
  }

  const ro = new ResizeObserver(resize);
  ro.observe(section);
  resize();

  section.addEventListener('mousemove', (e) => {
    const r = section.getBoundingClientRect();
    target.x = (e.clientX - r.left) / r.width;
    target.y = 1 - (e.clientY - r.top) / r.height;
  });

  section.addEventListener('mouseleave', () => {
    target.x = 0.5;
    target.y = 0.5;
  });

  function draw(t) {
    mouse.x += (target.x - mouse.x) * 0.05;
    mouse.y += (target.y - mouse.y) * 0.05;

    gl.uniform1f(uTime, t * 0.001);
    gl.uniform2f(uMouse, mouse.x, mouse.y);
    gl.clear(gl.COLOR_BUFFER_BIT);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);

    raf = requestAnimationFrame(draw);
  }

  // Pause RAF when the hero scrolls off-screen.
  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        if (!raf) raf = requestAnimationFrame(draw);
      } else {
        cancelAnimationFrame(raf);
        raf = null;
      }
    });
  });
  io.observe(section);
}

// Bail out entirely if the user prefers reduced motion.
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  document.querySelectorAll('[data-hero-webgl="true"]').forEach(initHero);
}
