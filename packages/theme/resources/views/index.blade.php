@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  @if (is_front_page() || is_home())
    {!! \App\faq_markup(3, 'home') !!}
  @endif

  @if (! have_posts())
    <x-alert type="warning">
      {!! __('Sorry, no results were found.', 'esctt') !!}
    </x-alert>

    {!! get_search_form(false) !!}
  @endif

  @while(have_posts()) @php(the_post())
    @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
  @endwhile

  {!! get_the_posts_navigation() !!}
@endsection

@section('sidebar')
  @include('sections.sidebar')
@endsection
