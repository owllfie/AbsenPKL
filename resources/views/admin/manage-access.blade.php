@extends('layouts.admin')

@section('title', $pageTitle)
@section('admin_title', $pageTitle)

@section('admin_content')
    <section class="page-panel">
        <div class="page-panel-header">
            <div>
                <p class="eyebrow">{{ $pageTitle }}</p>
                <p class="lede">{{ $pageDescription }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('manage-access.update') }}" class="manage-access-form">
            @csrf
            <div class="table-wrap">
                <table class="data-table access-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            @foreach ($modules as $module)
                                <th>{{ $module['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td><strong>{{ $role['name'] }}</strong></td>
                                @foreach ($modules as $moduleKey => $module)
                                    <td>
                                        <label class="access-checkbox">
                                            <input
                                                type="checkbox"
                                                name="access[{{ $role['id'] }}][{{ $moduleKey }}]"
                                                value="1"
                                                @checked($role['access'][$moduleKey] ?? false)
                                            >
                                            <span>Allow</span>
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="page-panel-actions">
                <button type="submit" class="primary-button">Save Access</button>
            </div>
        </form>
    </section>
@endsection
