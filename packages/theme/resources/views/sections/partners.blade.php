@php
  $items = [];

  foreach ($partners as $partner) {
      $name = trim((string) $partner->post_title);

      if ($name === '') {
          continue;
      }

      $url = esctt_sanitize_partner_url((string) get_post_meta($partner->ID, ESCTT_PARTNER_URL_META, true));
      $label = esc_html($name);

      if ($url !== '') {
          $label = sprintf(
              '<a href="%s" target="_blank" rel="noopener noreferrer">%s<span class="screen-reader-text"> (%s)</span></a>',
              esc_url($url),
              $label,
              esc_html__('ouvre dans une nouvelle fenêtre', 'esctt'),
          );
      }

      $items[] = sprintf('<li>%s</li>', $label);
  }

  if ($items === []) {
      return;
  }
@endphp

<section class="esctt-partners" aria-labelledby="esctt-partners-title"><h2 id="esctt-partners-title">{{ esc_html__('Partenaires', 'esctt') }}</h2><ul>{!! implode('', $items) !!}</ul></section>