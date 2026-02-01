@extends('errors::minimal')

@section('title', 'Unauthorized')
@section('code', '401')
@section('icon', 'ki-lock')
@section('color', 'warning')
@section('message', 'You need to be authenticated to access this resource. Please log in and try again.')
