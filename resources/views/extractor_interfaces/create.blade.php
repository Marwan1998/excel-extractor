@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                     @lang('models/extractorInterfaces.singular')
                </div>
            </div>
        </div>
    </section>


    <section class="content-header pt-0 pb-0">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-sm-4">
                    {{-- <div>
                        <p class="m-0">Empty files</p>
                        <a href="http://" download>DDR</a>
                        <a href="http://" download>DWR</a>                        
                    </div> --}}
                    <a href="#" class="btn btn-danger btn-sm">Empty DDR&DWR</a>
                </div>
                <div class="col-sm-6"></div>
                <div class="col-sm-2 text-right">
                    <a href="javascript:void(0)" onclick="downloadBoth()" class="btn btn-success btn-sm">Download DDR & DWR</a>
                    <a id="dl-ddr" href="{{ route('extractorInterfaces.downloadSingle', 'DDR.xlsx') }}" style="display:none"></a>
                    <a id="dl-dwr" href="{{ route('extractorInterfaces.downloadSingle', 'DWR.xlsx') }}" style="display:none"></a>
                </div>
            </div>
        </div>
    </section>


    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::open(['route' => 'extractorInterfaces.store', 'files' => true]) !!}

            <div class="card-body">
                <div class="row">
                    @include('extractor_interfaces.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('extractorInterfaces.create') }}" class="btn btn-default">
                    @lang('crud.cancel')
                </a>
                
                <a href="javascript:void(0)" id="validate-btn" class="btn btn-default">
                    @lang('models/extractorInterfaces.validateReportData')
                </a>
            </div>

            {!! Form::close() !!}

        </div>


        <div id="report-result-container" style="display: none; margin-top: 20px;">
            <div class="card">
                <div class="card-header">Validation Results</div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <pre id="json-renderer"></pre>
                </div>
            </div>
        </div>


    </div>
@endsection


@push('page_scripts')
    <script>
        function downloadBoth() {
            document.getElementById('dl-ddr').click();
            // Small delay to ensure the browser doesn't block the second "pop-up"
            setTimeout(() => {
                document.getElementById('dl-dwr').click();
            }, 500);
        }
    </script>

    <script>
        document.getElementById('validate-btn').addEventListener('click', function(e) {
            e.preventDefault();
            
            const btn = this;
            const container = document.getElementById('report-result-container');
            const renderer = document.getElementById('json-renderer');
            const form = btn.closest('form');

            // 1. Define the fields we need to check
            const fields = {
                'file_name': document.querySelector('input[name="file_name"]'),
                'company_name': document.querySelector('select[name="company_name"]'),
                'report_type': document.querySelector('select[name="report_type"]'),
                'date': document.getElementById('date')
            };

            let isValid = true;
            let firstErrorField = null;

            // 2. Loop through fields to validate
            Object.keys(fields).forEach(key => {
                const element = fields[key];
                let value = element.value;
                
                // Special check for file input
                if (key === 'file_name') {
                    value = element.files.length > 0 ? 'filled' : '';
                }

                if (!value || value.trim() === "") {
                    element.classList.add('is-invalid'); // Adds Bootstrap red border
                    isValid = false;
                    if (!firstErrorField) firstErrorField = element;
                } else {
                    element.classList.remove('is-invalid');
                    element.classList.add('is-valid');
                }
            });

            // 3. Stop if invalid
            if (!isValid) {
                if (firstErrorField) firstErrorField.focus();
                alert("Please fill in all required fields.");
                return;
            }

            // 4. If valid, proceed with AJAX
            const formData = new FormData(form);
            btn.innerText = 'Validating...';
            btn.classList.add('disabled');
            container.style.display = 'none'; // Hide previous results

            fetch("{{ route('extractorInterfaces.validateReportData') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) throw response;
                return response.json();
            })
            .then(data => {
                btn.innerText = "@lang('crud.validateReportData')";
                btn.classList.remove('disabled');
                
                container.style.display = 'block';
                renderer.textContent = JSON.stringify(data, null, 4);
                container.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(async (error) => {
                btn.innerText = "@lang('crud.validateReportData')";
                btn.classList.remove('disabled');
                
                if (error.status === 422) {
                    const errors = await error.json();
                    alert("Server Validation Failed: " + JSON.stringify(errors.errors));
                } else {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                }
            });
        });

        // Optional: Remove the red border when the user starts typing/selecting
        document.querySelectorAll('.form-control, .custom-file-input, .custom-select').forEach(input => {
            input.addEventListener('change', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
@endpush