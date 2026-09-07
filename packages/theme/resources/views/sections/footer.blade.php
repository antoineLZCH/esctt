<footer class="content-info">
  <div class="site-shell footer-layout">
    <div>
      <p class="footer-name">{{ $siteName }}</p>
      <p>Un club familial à Colombes pour découvrir et progresser.</p>
    </div>

    <nav class="nav-footer" aria-label="{{ __('Navigation du pied de page', 'esctt') }}">
      @if (has_nav_menu('footer_navigation'))
        {!! wp_nav_menu(['theme_location' => 'footer_navigation', 'menu_class' => 'nav-list', 'echo' => false]) !!}
      @else
        <ul class="nav-list">
          @foreach ($footerNavigation as $item)
            <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
          @endforeach
        </ul>
      @endif
    </nav>

    @php(dynamic_sidebar('sidebar-footer'))
  </div>
</footer>
