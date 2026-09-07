<section class="esctt-pricing" aria-labelledby="esctt-pricing-title">
  <h2 id="esctt-pricing-title">{{ __('Comparer les tarifs', 'esctt') }}</h2>
  <p class="esctt-pricing__note">
    {{ __('Les montants sont maintenus par l’administration du club.', 'esctt') }}
  </p>

  <div class="esctt-pricing__matrix" data-pricing-matrix>
    @forelse ($pricing['tariff_categories'] as $category)
      <article class="esctt-pricing__category" data-tariff-category>
        <h3>{{ $category['label'] }}</h3>

        <dl class="esctt-pricing__options">
          @foreach ($category['options'] as $option)
            <div
              class="esctt-pricing__option"
              data-pricing-option
              data-location="{{ $option['location_key'] }}"
              data-practice="{{ $option['practice_key'] }}"
            >
              <dt>
                <span>{{ $option['location_label'] }}</span>
                <span>{{ $option['practice_label'] }}</span>
              </dt>
              <dd>{{ $option['amount'] }}</dd>
            </div>
          @endforeach
        </dl>
      </article>
    @empty
      <p class="esctt-pricing__empty">
        {{ __('Ajoutez une catégorie tarifaire dans l’administration pour afficher les montants.', 'esctt') }}
      </p>
    @endforelse
  </div>

  @if ($pricing['player_profiles'] !== [])
    <section class="esctt-pricing__profiles" aria-labelledby="esctt-pricing-profiles-title">
      <h2 id="esctt-pricing-profiles-title">{{ __('Profils de joueur', 'esctt') }}</h2>
      <p>{{ __('Les profils orientent la pratique ; ils sont distincts des catégories tarifaires.', 'esctt') }}</p>
      <ul>
        @foreach ($pricing['player_profiles'] as $profile)
          <li data-player-profile>{{ $profile['label'] }}</li>
        @endforeach
      </ul>
    </section>
  @endif

  <dl class="esctt-pricing__extras">
    <div>
      <dt>{{ __('Prix du maillot', 'esctt') }}</dt>
      <dd>{{ $pricing['jersey_price'] }}</dd>
    </div>
    <div>
      <dt>{{ __('Pass+ accepté', 'esctt') }}</dt>
      <dd>{{ $pricing['pass_plus_acceptance'] }}</dd>
    </div>
  </dl>
</section>
