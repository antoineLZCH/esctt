@php
  $items = [];

  foreach ($partners as $partner) {
      $name = trim((string) $partner->post_title);

      if ($name === '') {
          continue;
      }

      $url = esctt_sanitize_partner_url((string) get_post_meta($partner->ID, ESCTT_PARTNER_URL_META, true));
      $description = trim((string) get_post_meta($partner->ID, ESCTT_PARTNER_DESCRIPTION_META, true));
      $logo = (string) get_the_post_thumbnail($partner->ID, 'medium', [
          'class' => 'esctt-partners__logo',
      ]);
      $details = '';

      if ($logo !== '') {
          $details .= '<div class="esctt-partners__logo-wrap">' . $logo . '</div>';
      }

      if ($description !== '') {
          $details .= '<div class="esctt-partners__description">' . wpautop(wp_kses_post($description)) . '</div>';
      }

      $label = esc_html($name);

      if ($url !== '') {
          $label = sprintf(
              '<a href="%s" target="_blank" rel="noopener noreferrer">%s<span class="screen-reader-text"> (%s)</span></a>',
              esc_url($url),
              $label,
              esc_html__('ouvre dans une nouvelle fenêtre', 'esctt'),
          );
      }

      $items[] = sprintf('<li>%s%s</li>', $details, $label);
  }

  if ($items === []) {
      return;
  }
@endphp

<section class="esctt-partners" aria-labelledby="esctt-partners-title"><h2 id="esctt-partners-title">{{ esc_html__('Partenaires', 'esctt') }}</h2><ul>{!! implode('', $items) !!}</ul></section>