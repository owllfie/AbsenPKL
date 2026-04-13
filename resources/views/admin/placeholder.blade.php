@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel placeholder-panel">
        <p class="eyebrow">{{ $pageTitle }}</p>
        <h2>Halaman masih placeholder</h2>
        <p class="lede">{{ $pageDescription }}</p>
    </section>
@endsection
