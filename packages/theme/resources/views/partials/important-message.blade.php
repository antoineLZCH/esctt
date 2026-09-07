@php($message = $importantMessage())

@if ($message)
  <aside class="esctt-important-message" aria-label="{{ __('Information importante', 'esctt') }}">
    <div class="esctt-important-message__content">
      <p class="esctt-important-message__text">{{ $message['message'] }}</p>

      @if ($message['detail_url'])
        <a
          class="esctt-important-message__link"
          href="{{ esc_url($message['detail_url']) }}"
          aria-label="{{ $message['detail_label'] }}"
        >{{ $message['detail_label'] }}</a>
      @endif
    </div>
  </aside>
@endif
