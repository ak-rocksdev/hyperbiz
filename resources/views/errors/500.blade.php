@extends('errors::minimal')

@section('title', 'Server Error')
@section('code', '500')
@section('icon', 'ki-cross-circle')
@section('color', 'danger')
@section('message', 'Something went wrong on our end. Please try again later or contact support if the problem persists.')
