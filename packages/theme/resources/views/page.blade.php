@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @if (is_page('inscriptions'))
      @include('partials.content-inscriptions')
    @else
      @includeFirst(['partials.content-page', 'partials.content'])

      @if (is_front_page())
        {!! \App\faq_markup(3, 'home') !!}
      @endif
    @endif
  @endwhile
@endsection
