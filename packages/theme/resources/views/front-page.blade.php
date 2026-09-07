@extends('layouts.app')

@section('content')
  <section class="club-hero site-shell" aria-labelledby="home-title">
    <p class="eyebrow">ES Colombienne Tennis de table</p>
    <h1 id="home-title">Un club familial à Colombes pour découvrir et progresser.</h1>
    <p class="hero-intro">Jeunes, débutants et adultes peuvent trouver leur place au club et avancer ensemble.</p>
    <div class="home-actions">
      <a class="button" href="#horaires">Voir les horaires</a>
      <a class="button button-secondary" href="#tarifs">Voir les tarifs</a>
    </div>
  </section>

  <section class="home-section site-shell" aria-labelledby="audiences-title">
    <p class="eyebrow">L’identité du club</p>
    <h2 id="audiences-title">Une place pour chaque joueur</h2>
    <div class="audience-grid">
      <article class="audience-card">
        <h3>Jeunes</h3>
        <p>Le club accueille les jeunes joueurs mineurs.</p>
      </article>
      <article class="audience-card">
        <h3>Débutants</h3>
        <p>Un cadre pour découvrir le tennis de table et apprendre.</p>
      </article>
      <article class="audience-card">
        <h3>Adultes loisirs</h3>
        <p>Une pratique loisir pour jouer et progresser ensemble.</p>
      </article>
      <article class="audience-card">
        <h3>Adultes compétition</h3>
        <p>Une pratique pour développer son jeu en compétition.</p>
      </article>
    </div>
  </section>

  {!! \App\render_partners() !!}

  <section id="horaires" class="home-section home-section-muted site-shell skip-target" tabindex="-1" aria-labelledby="schedules-title">
    <p class="eyebrow">Pratiquer</p>
    <h2 id="schedules-title">Horaires et lieux</h2>
    <p>Retrouvez tous les créneaux de la semaine, leur lieu et les profils auxquels ils s’adressent.</p>
  </section>

  <section id="tarifs" class="home-section site-shell skip-target" tabindex="-1" aria-labelledby="pricing-title">
    <p class="eyebrow">Adhérer</p>
    <h2 id="pricing-title">Tarifs</h2>
    <p>Les tarifs seront présentés par catégorie, lieu de résidence et pratique, sans confondre catégorie tarifaire et profil de joueur.</p>
    <a class="text-link" href="{{ home_url('/tarifs/') }}">Consulter les tarifs</a>
  </section>
@endsection
