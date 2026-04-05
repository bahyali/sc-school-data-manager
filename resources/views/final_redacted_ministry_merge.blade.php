<h2>Merge Final Redacted + Ministry CSV</h2>
<p>Upload your <strong>Final Redacted</strong> CSV (all columns are kept, in order) and the <strong>ministry</strong> CSV (BSID, OSSD Credits Offered, Website, Level). Only rows whose BSID appears in the ministry file are included. The download adds <em>Website</em>, <em>OSSD Credits Offered</em>, and <em>M-School Level</em> (ministry Level) after all columns from the first file. There is no level comparison and no extra flag column.</p>
<p>The first file must include a <strong>BSID</strong> column (header name matched case-insensitively).</p>

<form method="POST" action="{{ route('finalRedactedMinistryMerge') }}" enctype="multipart/form-data">
    @csrf

    <div class="col-md-8 offset-md-4" style="margin-bottom: 1rem;">
        <label for="primary_file">Final Redacted CSV</label><br>
        <input type="file" name="primary_file" id="primary_file" accept=".csv,.txt,text/csv" required>
    </div>

    <div class="col-md-8 offset-md-4" style="margin-bottom: 1rem;">
        <label for="ministry_file">Ministry CSV</label><br>
        <input type="file" name="ministry_file" id="ministry_file" accept=".csv,.txt,text/csv" required>
    </div>

    <div class="col-md-8 offset-md-4">
        <button type="submit" class="btn btn-primary">Merge &amp; download CSV</button>
    </div>
</form>
