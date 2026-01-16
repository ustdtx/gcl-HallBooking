@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Import Members - Map Columns</h1>
    <form method="POST" action="{{ route('admin.members.import.csv.map') }}">
        @csrf
        <input type="hidden" name="csv_path" value="{{ $csv_path }}">
        <div class="mb-3">
            <label>Map CSV columns to member fields:</label>
            <div class="row">
                @foreach($fields as $field => $label)
                <div class="col-md-3 mb-2">
                    <label>{{ $label }}</label>
                    <select name="mapping[{{ $field }}]" class="form-select" required>
                        <option value="">-- Select column --</option>
                        @foreach($csv_headers as $i => $header)
                        <option value="{{ $i }}" @if(isset($suggested[$field]) && $suggested[$field] == $i) selected @endif>{{ $header }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
            </div>
        </div>
        <h5>Preview (first 5 rows):</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        @foreach($csv_headers as $header)
                        <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($csv_preview as $row)
                    <tr>
                        @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Import Members</button>
    </form>
</div>
@endsection
