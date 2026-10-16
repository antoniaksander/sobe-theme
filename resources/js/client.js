/**
 * Client JavaScript entry. Client-owned: the platform ships this file empty
 * and never changes it, so a fork's code here never conflicts on sync.
 *
 * Imported by app.js before Alpine starts and before app.js's own code runs
 * (ES imports are evaluated first). That means:
 *
 * - Alpine.data()/Alpine.store() calls here register new components directly.
 * - To replace a platform component of the same name, register it inside
 *   document.addEventListener('alpine:init', ...), which runs after app.js
 *   has registered the platform's.
 * - Side-effect imports of the fork's own modules go here.
 *
 *   import Alpine from 'alpinejs';
 *   import './my-client-module.js';
 *
 *   Alpine.data('myComponent', () => ({ open: false }));
 */
