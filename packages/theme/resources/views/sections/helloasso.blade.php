<section class="esctt-helloasso" aria-labelledby="esctt-helloasso-title">
  <h2 id="esctt-helloasso-title">{!! esc_html__('Adhérer via HelloAsso', 'esctt') !!}</h2>
  <p>{!! esc_html__('Le formulaire d’adhésion est hébergé par HelloAsso.', 'esctt') !!}</p>
  <p class="esctt-helloasso__policy">{!! esc_html__('Les pièces d’adhésion sont collectées par HelloAsso, pas par le site du club.', 'esctt') !!}</p>
  <p class="esctt-helloasso__fallback">
    <a class="wp-element-button" href="{{ esc_url($membershipUrl) }}" target="_blank" rel="noopener noreferrer">
      {!! esc_html__('Ouvrir le formulaire d’adhésion HelloAsso', 'esctt') !!}
      <span class="screen-reader-text">{!! esc_html__('(ouvre dans une nouvelle fenêtre)', 'esctt') !!}</span>
    </a>
  </p>
  <div class="esctt-helloasso__widget">
    <iframe
      id="haWidget"
      data-helloasso-widget="true"
      title="{{ esc_attr__('Formulaire d’adhésion HelloAsso', 'esctt') }}"
      src="{{ esc_url($widgetUrl) }}"
      width="100%"
      style="width: 100%; height: 750px; border: none;"
      loading="lazy"
      allowtransparency="true"
      scrolling="auto"
    ></iframe>
  </div>
</section>
