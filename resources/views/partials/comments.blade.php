@if (! post_password_required())
  <section id="comments" class="comments">
    @if ($responses())
      <h2 class="text-2xl font-bold text-heading mb-8">
        {!! $title !!}
      </h2>

      <ol class="comment-list">
        {!! $responses !!}
      </ol>

      @if ($paginated())
        <nav class="comment-navigation" aria-label="{{ __('Comment navigation', 'sobe') }}">
          <div>
            {!! $previous !!}
          </div>
          <div>
            {!! $next !!}
          </div>
        </nav>
      @endif
    @endif

    @if ($closed())
      <div class="comments-closed">
        {!! __('Comments are closed.', 'sobe') !!}
      </div>
    @endif

    @if (comments_open())
      <div class="comment-respond">
        <h3 class="comment-reply-title">
          {!! __('Leave a Reply', 'sobe') !!}
        </h3>
        {!! comment_form([
          'class_form' => 'comment-form',
          'title_reply' => '',
          'comment_notes_before' => '',
          'comment_notes_after' => '',
          'label_submit' => __('Post Comment', 'sobe'),
        ]) !!}
      </div>
    @endif
  </section>
@endif