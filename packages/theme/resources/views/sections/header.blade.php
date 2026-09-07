<header class="banner">
  <div class="site-shell site-header">
    <a class="brand" href="{{ home_url('/') }}">
      {{ $siteName }}
    </a>

    <nav class="nav-primary" aria-label="{{ __('Navigation principale', 'esctt') }}">
      @if (has_nav_menu('primary_navigation'))
        {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav-list', 'echo' => false]) !!}
      @else
        <ul class="nav-list">
          @foreach ($clubNavigation as $item)
            <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
          @endforeach
        </ul>
      @endif
    </nav>
  </div>
</header>
