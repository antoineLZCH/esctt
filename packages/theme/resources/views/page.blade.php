@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @if (is_page('inscriptions'))
      @include('partials.content-inscriptions')
    @else
      @includeFirst(['partials.content-page', 'partials.content'])
    @endif
  @endwhile
@endsection
