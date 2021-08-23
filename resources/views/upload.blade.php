


<form method="POST" action="{{ route('import') }}" enctype="multipart/form-data">
		@csrf

	<div class="col-md-8 offset-md-4">

		<input type="file" name="schools_file">
		<input type="hidden" name="school_status" value="active">
	    <button type="submit" class="btn btn-primary">
	        {{ __('Upload') }}
	    </button>
	</div>
</form>