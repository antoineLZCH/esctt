@if ($importantMessage)
  <aside class="esctt-important-message" aria-label="{{ __('Information importante', 'esctt') }}">
    <div class="esctt-important-message__content">
      <p class="esctt-important-message__text">{{ $importantMessage['message'] }}</p>

      @if ($importantMessage['detail_url'])
        <a
          class="esctt-important-message__link"
          href="{{ esc_url($importantMessage['detail_url']) }}"
          aria-label="{{ $importantMessage['detail_label'] }}"
        >{{ $importantMessage['detail_label'] }}</a>
      @endif
    </div>
  </aside>
@endif
