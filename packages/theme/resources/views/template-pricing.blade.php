{{--
  Template Name: Tarifs
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @include('partials.content-page')
    {!! \App\pricing_matrix(\App\pricing_model()) !!}
  @endwhile
@endsection
