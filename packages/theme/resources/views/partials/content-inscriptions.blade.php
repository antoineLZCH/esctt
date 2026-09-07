<article @php(post_class('esctt-registration'))>
  {!! $hero !!}

  <div class="esctt-registration__sections">
    <section aria-labelledby="registration-steps-title">
      <h2 id="registration-steps-title">{{ __('Les étapes de l’inscription', 'esctt') }}</h2>

      <ol>
        @foreach ($steps as $step)
          <li>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['description'] }}</p>
          </li>
        @endforeach
      </ol>
    </section>

    <section aria-labelledby="registration-pps-title">
      <h2 id="registration-pps-title">{{ __('Parcours Prévention Santé (PPS)', 'esctt') }}</h2>
      <p>{{ __('Depuis le 1er juillet 2026, le PPS remplace le questionnaire de santé pour les licenciés majeurs dans le cadre de la saison 2026–2027.', 'esctt') }}</p>

      <ul>
        @foreach ($preventionHealth as $item)
          <li>
            <h3>{{ $item['heading'] }}</h3>
            <p>{{ $item['text'] }}</p>
          </li>
        @endforeach
      </ul>

      <p>{{ __('Un certificat médical peut se substituer au PPS lorsque votre situation le nécessite.', 'esctt') }}</p>
      <p><a href="https://www.fftt.com/actualites/ping-citoyen/parcours-prevention-sante/">{{ __('Consulter les informations de la FFTT sur le PPS', 'esctt') }}</a></p>
    </section>

    <section aria-labelledby="registration-documents-title">
      <h2 id="registration-documents-title">{{ __('Documents utiles pour la saison', 'esctt') }}</h2>
      <p class="registration-policy">{{ $documentsPolicy }}</p>

      @if ($documents)
        <ul>
          @foreach ($documents as $document)
            <li>
              <a href="{{ esc_url($document['url']) }}">{{ sprintf(__('Télécharger : %s', 'esctt'), $document['title']) }}</a>
              @if ($document['season'])
                <span> — {{ $document['season'] }}</span>
              @endif
            </li>
          @endforeach
        </ul>
      @else
        <p>{{ __('Aucun document de saison n’est actuellement publié.', 'esctt') }}</p>
      @endif
    </section>
  </div>

  <div class="esctt-registration__editor-content">
    {!! $editorContent !!}
  </div>

  @if ($pagination())
    <nav class="page-nav" aria-label="{{ __('Page', 'esctt') }}">
      {!! $pagination !!}
    </nav>
  @endif
</article>
