
<h2>Onsis All Schools Importing</h2>

<form method="POST" action="{{ route('excelImporting') }}" enctype="multipart/form-data">
		@csrf

	<div class="col-md-8 offset-md-4">

		<input type="file" name="schools_file">
		<input type="hidden" name="data_src_name" value="onsis_all_schools">
		<input type="hidden" name="school_status" value="all">

	    <button type="submit" class="btn btn-primary">
	        {{ __('Upload') }}
	    </button>
	</div>
</form>