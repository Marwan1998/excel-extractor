<!-- File Name Field -->
<div class="form-group col-sm-3">
    {!! Form::label('file_name', __('models/extractorInterfaces.fields.file_name').':') !!}
    <div class="input-group">
        <div class="custom-file">
            {!! Form::file('file_name', ['class' => 'custom-file-input', 'required' => true, 'accept' => '.pdf, .docx, .xlsx']) !!}
            {!! Form::label('file_name', 'Choose file', ['class' => 'custom-file-label']) !!}
        </div>
    </div>
</div>
<div class="clearfix"></div>


<!-- Company Name Field -->
<div class="form-group col-sm-3">
    {!! Form::label('company_name', __('models/extractorInterfaces.fields.company_name').':') !!}
    {!! Form::select('company_name', $companies, null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Report Type Field -->
<div class="form-group col-sm-3">
    {!! Form::label('report_type', __('models/extractorInterfaces.fields.report_type').':') !!}
    {!! Form::select('report_type', ['drilling' => 'drilling', 'workover' => 'workover'], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Date Field -->
<div class="form-group col-sm-3">
    {!! Form::label('date', __('models/extractorInterfaces.fields.date').':') !!}
    {!! Form::text('date', null, ['class' => 'form-control','id'=>'date']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#date').datetimepicker({
            // format: 'YYYY-MM-DD',
            format: 'MM/DD/YYYY',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush


