<h2>Merge Schoolcred + Ministry CSV</h2>
<p>Upload your schoolcred file (School Name, BSID, OSSD, School Type, Grade Range) and ministry file (BSID, OSSD Credits Offered, Website, Level). The download includes only rows where BSID exists in both files, and appends ministry <em>Website</em>, <em>OSSD Credits Offered</em>, and <em>M-School Level</em>.</p>

<form method="POST" action="{{ route('schoolcredMinistryMerge') }}" enctype="multipart/form-data">
    @csrf

    <div class="col-md-8 offset-md-4" style="margin-bottom: 1rem;">
        <label for="schoolcred_file">Schoolcred CSV</label><br>
        <input type="file" name="schoolcred_file" id="schoolcred_file" accept=".csv,.txt,text/csv" required>
    </div>

    <div class="col-md-8 offset-md-4" style="margin-bottom: 1rem;">
        <label for="ministry_file">Ministry CSV</label><br>
        <input type="file" name="ministry_file" id="ministry_file" accept=".csv,.txt,text/csv" required>
    </div>

    <div class="col-md-8 offset-md-4">
        <button type="submit" class="btn btn-primary">Merge &amp; download CSV</button>
    </div>
</form>
