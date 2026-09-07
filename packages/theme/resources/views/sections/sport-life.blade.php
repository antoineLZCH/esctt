<section class="esctt-sport-life" aria-labelledby="esctt-sport-life-title">
  <div class="esctt-sport-life__intro">
    <article class="esctt-sport-life__tournament">
      <p class="esctt-sport-life__eyebrow">{!! esc_html__('Tournoi interne', 'esctt') !!}</p>
      <h2 id="esctt-sport-life-title">{!! esc_html__('Tournoi des familles', 'esctt') !!}</h2>
      <p>{!! esc_html__('Le tournoi des familles est le temps fort des tournois internes du club.', 'esctt') !!}</p>
      <p>{!! esc_html__('Un rendez-vous pour partager le tennis de table en famille et entre membres.', 'esctt') !!}</p>
      @if ($helloAssoUrl)
        <p class="esctt-sport-life__cta">
          <a class="wp-element-button" href="{{ esc_url($helloAssoUrl) }}" target="_blank" rel="noopener noreferrer">
            {!! esc_html__('S’inscrire au tournoi des familles sur HelloAsso', 'esctt') !!}
            <span class="screen-reader-text">{!! esc_html__('(ouvre dans une nouvelle fenêtre)', 'esctt') !!}</span>
          </a>
        </p>
      @endif
    </article>
    <article class="esctt-sport-life__competitions" aria-labelledby="esctt-sport-life-competitions-title">
      <p class="esctt-sport-life__eyebrow">{!! esc_html__('Pratique sportive', 'esctt') !!}</p>
      <h2 id="esctt-sport-life-competitions-title">{!! esc_html__('Compétitions FFTT', 'esctt') !!}</h2>
      <p>{!! esc_html__('La compétition FFTT complète la vie du club. Cette présentation reste volontairement concise, sans résultats ni calendrier détaillé.', 'esctt') !!}</p>
    </article>
  </div>
  <figure class="esctt-sport-life__jersey">
    <div class="esctt-sport-life__photos">
      <img src="{{ esc_url($frontImage) }}" alt="{!! esc_attr__('Photo du maillot de l’ES Colombienne vu de face', 'esctt') !!}" width="900" height="1600" loading="lazy">
      <img src="{{ esc_url($backImage) }}" alt="{!! esc_attr__('Photo du maillot de l’ES Colombienne vu de dos', 'esctt') !!}" width="900" height="1600" loading="lazy">
    </div>
    <figcaption>{!! esc_html__('Maillot actuel du club, photographié de face et de dos.', 'esctt') !!}</figcaption>
  </figure>
</section>
