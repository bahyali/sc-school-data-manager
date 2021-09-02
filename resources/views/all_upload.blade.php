<h2>Import Excel</h2>

<form method="POST" action="{{ route('excelImporting') }}" enctype="multipart/form-data">
	@csrf

	<div class="col-md-8 offset-md-4">
		<label>Data Source</label>
		<select name="data_src_name">
			@foreach($data_sources as $data_source)
			<option value="{{$data_source}}">{{$data_source}}</option>
			@endforeach
		</select>
	</div>
	<div class="col-md-8 offset-md-4">

		<label>File</label>
		<input type="file" name="schools_file">
		<button type="submit" class="btn btn-primary">
			{{ __('Upload') }}
		</button>
	</div>
</form>