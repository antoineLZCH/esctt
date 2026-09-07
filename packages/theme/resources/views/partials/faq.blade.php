@php
  $headingId = "faq-{$context}-heading";
@endphp

@if ($items !== [])
  <section class="faq faq--{{ $context }}" aria-labelledby="{{ $headingId }}">
    <h2 id="{{ $headingId }}">{{ __('Questions fréquentes', 'esctt') }}</h2>

    <div class="faq-list">
      @foreach ($items as $index => $item)
        @php
          $questionId = "faq-{$context}-question-{$index}";
          $answerId = "faq-{$context}-answer-{$index}";
        @endphp

        <details id="faq-{{ $context }}-item-{{ $index }}" class="faq-item" aria-labelledby="{{ $questionId }}">
          <summary id="{{ $questionId }}" aria-controls="{{ $answerId }}">{{ $item['question'] }}</summary>
          <div id="{{ $answerId }}" class="faq-answer" role="region" aria-labelledby="{{ $questionId }}">
            {!! $item['answer'] !!}
          </div>
        </details>
      @endforeach
    </div>

    @if ($showLink)
      <a class="faq-link" href="{{ home_url('/faq/') }}">
        {{ __('Voir toutes les questions', 'esctt') }}
      </a>
    @endif
  </section>
@endif
