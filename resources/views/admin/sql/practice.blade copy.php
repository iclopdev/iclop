@extends('admin/admin')
@section('css')
    <link rel="stylesheet" href="{{ asset('lte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lte/plugins/codemirror/lib/codemirror.css') }}">
    <link rel="stylesheet" href="{{ asset('lte/plugins/codemirror/theme/dracula.css') }}">
@endsection
@section('js')
    <script src="{{ asset('lte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/codemirror/lib/codemirror.js') }}"></script>
    <script src="{{ asset('lte/plugins/codemirror/mode/sql/sql.js') }}"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/super-build/ckeditor.js"></script>
    <script>
        const formContent = $('form .modal-body').html();
        var cm = [],
            ck = [],
            no = 1;

        $(document).ready(function() {
            loads();
        });

        const loads = () => {
            const data = $('tbody').html();
            $.ajax({
                type: "GET",
                url: "{{ route('admin sql practice read') }}",
                beforeSend: function() {
                    $('#content tbody').html('');
                },
                error: function() {
                    console.log('error');
                },
                success: function(response) {
                    if (response.length == 0) {
                        $('#content tbody').append('<tr><td colspan="4" class="align-middle text-center text-muted">tidak ada data</td></tr>');
                    }

                    $.each(response, function(index, value) {
                        $('#content tbody').append('<tr><td class="align-middle text-center">' + (index + 1) + '</td><td class="align-middle">' + value.name + '</td><td class="align-middle text-center">' + value.question + '</td><td class="align-middle text-center"><div class="btn-group"><button class="btn btn-sm btn-success mr-1" data-action="update" data-id="' + value.id + '"><i class="fa fa-pen"></i></button></div><div class="btn-group"><button class="btn btn-sm btn-danger" data-action="delete" data-id="' + value.id + '"><i class="fa fa-trash"></i></button></div></td></tr>');
                    });

                    reloads();
                    creates();
                    updates();
                    deletes();
                }
            });
        }

        const reloads = () => {
            $('[data-action=reload]').unbind('click');
            $('[data-action=reload]').on('click', function() {
                loads();
            });
        }

        const creates = () => {
            $('[data-action=create]').unbind('click');
            $('[data-action=create]').on('click', function() {
                $('#formModal').on('shown.bs.modal', function() {
                    $('#forms .modal-body').html(formContent);

                    $('#formModalLabel').html('Tambah Modul Praktek');
                    no = 1;
                    cm = [];
                    ck = [];
                    $('#forms #question-tab').html('');
                    $('#forms #question-tabContent').html('');
                    formElement();
                    formSubmit();
                });

                $('#formModal').modal({
                    backdrop: 'static',
                    keyboard: false
                })
            });
        }

        const updates = () => {
            $('[data-action=update]').unbind('click');
            $('[data-action=update]').on('click', function() {
                let id = $(this).attr('data-id');
                let url = '{{ route('admin sql practice detail', ':id') }}';
                url = url.replace(":id", id);
                $.ajax({
                    type: "GET",
                    url: url,
                    beforeSend: function() {
                        $('button[data-action]').attr('disabled', true);
                    },
                    complete: function() {
                        $('button[data-action]').removeAttr('disabled');
                    },
                    error: function() {
                        $('button[data-action]').removeAttr('disabled');

                        Swal.fire({
                            title: 'Data Tidak Ditemukan',
                            text: "data yang anda maksud tidak ada di database",
                            type: 'error',
                            confirmButtonText: 'Tutup',
                        }).then(() => {
                            loads();
                        })
                    },
                    success: function(response) {
                        $('#formModal').unbind('shown.bs.modal');
                        $('#formModal').on('shown.bs.modal', function() {
                            $('#forms .modal-body').html(formContent);
                            $('#forms button[type=submit]').attr('data-id', id);
                            $('#forms #question-tab').html('');
                            $('#forms #question-tabContent').html('');

                            $.each(response, function(index, value) {
                                if ($('#forms [name=' + index + ']').length) {
                                    switch ($('#forms [name=' + index + ']').prop("tagName")) {
                                        case 'TEXTAREA':
                                            $('#forms [name=' + index + ']').html(value)
                                            break;

                                        case 'INPUT':
                                            if ($('#forms [name=' + index + ']').prop("type") != 'file') {
                                                $('#forms [name=' + index + ']').val(value);
                                            }
                                            break;

                                        default:
                                            break;
                                    }
                                }
                            });

                            if (response.question) {
                                no = 1;
                                cm = [];
                                ck = [];

                                $.each(response.question, function(index, value) {
                                    $('#forms button#question-add').attr('disabled', true);
                                    $('#forms #question-tab').append('<a class="nav-link" id="question-' + no + '-tab" data-toggle="pill" href="#question-' + no + '" role="tab" aria-controls="question-' + no + '" aria-selected="true">Soal ' + no + '</a>');
                                    $('#forms #question-tabContent').append('<div class="tab-pane fade" id="question-' + no + '" role="tabpanel" aria-labelledby="question-' + no + '-tab"><div class="form-group"><label for="question">Soal <code>*</code></label><textarea data-id="' + no + '" id="question-text-' + no + '">' + value.question + '</textarea></div><div class="form-group"><label for="question">Sintak Tes <code>*</code></label><textarea id="question-syntax-' + no + '">' + value.syntax + '</textarea></div></div>');

                                    if (typeof cm[no] == 'undefined') {
                                        cm[no] = CodeMirror.fromTextArea($('#forms #question-tabContent textarea#question-syntax-' + no)[0], {
                                            lineNumbers: true,
                                            styleActiveLine: true,
                                            mode: "sql",
                                            theme: "dracula",
                                        });
                                        cm[no].refresh();
                                    }

                                    if (typeof ck[no] == 'undefined') {
                                        CKEDITOR.ClassicEditor.create($('#forms #question-tabContent textarea#question-text-' + no)[0], {
                                            toolbar: {
                                                items: [
                                                    'heading', '|',
                                                    'alignment', '|',
                                                    'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                                                    'bold', 'italic', 'strikethrough', 'underline', 'code', 'removeFormat', '|',
                                                    'bulletedList', 'numberedList', 'todoList', '|',
                                                    'outdent', 'indent', '|',
                                                    'link', 'insertImage', 'blockQuote', 'insertTable', 'mediaEmbed', 'codeBlock', '|',
                                                    'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                                                    'sourceEditing'
                                                ],
                                                shouldNotGroupWhenFull: true
                                            },
                                            placeholder: '',
                                            list: {
                                                properties: {
                                                    styles: true,
                                                    startIndex: true,
                                                    reversed: true
                                                }
                                            },
                                            heading: {
                                                options: [{
                                                        model: 'paragraph',
                                                        title: 'Paragraph',
                                                        class: 'ck-heading_paragraph'
                                                    },
                                                    {
                                                        model: 'heading1',
                                                        view: 'h1',
                                                        title: 'Heading 1',
                                                        class: 'ck-heading_heading1'
                                                    },
                                                    {
                                                        model: 'heading2',
                                                        view: 'h2',
                                                        title: 'Heading 2',
                                                        class: 'ck-heading_heading2'
                                                    },
                                                    {
                                                        model: 'heading3',
                                                        view: 'h3',
                                                        title: 'Heading 3',
                                                        class: 'ck-heading_heading3'
                                                    },
                                                    {
                                                        model: 'heading4',
                                                        view: 'h4',
                                                        title: 'Heading 4',
                                                        class: 'ck-heading_heading4'
                                                    },
                                                    {
                                                        model: 'heading5',
                                                        view: 'h5',
                                                        title: 'Heading 5',
                                                        class: 'ck-heading_heading5'
                                                    },
                                                    {
                                                        model: 'heading6',
                                                        view: 'h6',
                                                        title: 'Heading 6',
                                                        class: 'ck-heading_heading6'
                                                    }
                                                ]
                                            },
                                            fontFamily: {
                                                options: [
                                                    'default',
                                                    'Arial, Helvetica, sans-serif',
                                                    'Courier New, Courier, monospace',
                                                    'Georgia, serif',
                                                    'Lucida Sans Unicode, Lucida Grande, sans-serif',
                                                    'Tahoma, Geneva, sans-serif',
                                                    'Times New Roman, Times, serif',
                                                    'Trebuchet MS, Helvetica, sans-serif',
                                                    'Verdana, Geneva, sans-serif'
                                                ],
                                                supportAllValues: true
                                            },
                                            fontSize: {
                                                options: [10, 12, 14, 'default', 18, 20, 22],
                                                supportAllValues: true
                                            },
                                            htmlSupport: {
                                                allow: [{
                                                    name: /.*/,
                                                    attributes: true,
                                                    classes: true,
                                                    styles: true
                                                }]
                                            },
                                            htmlEmbed: {
                                                showPreviews: true
                                            },
                                            link: {
                                                decorators: {
                                                    addTargetToExternalLinks: true,
                                                    defaultProtocol: 'https://',
                                                    toggleDownloadable: {
                                                        mode: 'manual',
                                                        label: 'Downloadable',
                                                        attributes: {
                                                            download: 'file'
                                                        }
                                                    }
                                                }
                                            },
                                            removePlugins: [
                                                'CKBox',
                                                'CKFinder',
                                                'EasyImage',
                                                'RealTimeCollaborativeComments',
                                                'RealTimeCollaborativeTrackChanges',
                                                'RealTimeCollaborativeRevisionHistory',
                                                'PresenceList',
                                                'Comments',
                                                'TrackChanges',
                                                'TrackChangesData',
                                                'RevisionHistory',
                                                'Pagination',
                                                'WProofreader',
                                                'MathType'
                                            ]
                                        }).then(e => {
                                            ck[$(e.sourceElement).attr('id').replace('question-text-', '')] = e;
                                        })
                                    }

                                    no++;
                                })
                            }

                            $('#formModalLabel').html('Ubah Modul Praktek');
                            formElement();
                            formSubmit();
                        });

                        $('#formModal').modal({
                            backdrop: 'static',
                            keyboard: false
                        })
                    }
                });
            });
        }

        const deletes = () => {
            $('[data-action=delete]').unbind('click');
            $('[data-action=delete]').on('click', function() {
                let id = $(this).attr('data-id');
                let url = '{{ route('admin sql practice delete', ':id') }}';
                url = url.replace(':id', id);

                Swal.fire({
                    title: 'Hapus Data',
                    text: "data yang telah dihapus tidak dapat dikembalikan, harap pastikan bahwa anda benar-benar yakin!",
                    type: 'warning',
                    confirmButtonText: 'Saya Yakin!',
                    cancelButtonText: 'Batal',
                    showCancelButton: true,
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    backdrop: true,
                    preConfirm: () => {
                        return fetch(url)
                            .then(response => {
                                return response;
                            })
                    },
                }).then((result) => {
                    if (typeof result.dismiss == 'undefined') {
                        if (result.value.ok) {
                            Swal.fire({
                                title: 'Hapus Data',
                                text: "data berhasil dihapus",
                                type: 'success',
                                confirmButtonText: 'Mantap!',
                            }).then(() => {
                                loads();
                            })
                        } else {
                            Swal.fire({
                                title: 'Hapus Data',
                                text: "data gagal dihapus",
                                type: 'error',
                                confirmButtonText: 'Tutup',
                            }).then(() => {
                                loads();
                            })
                        }
                    }
                })
            });
        }

        const formElement = () => {
            $('#forms button#question-add').unbind('click');
            $('#forms button#question-add').on('click', function() {
                $('#forms button#question-add').attr('disabled', true);
                $('#forms #question-tab').append('<a class="nav-link" id="question-' + no + '-tab" data-toggle="pill" href="#question-' + no + '" role="tab" aria-controls="question-' + no + '" aria-selected="true">Soal ' + no + '</a>');
                $('#forms #question-tabContent').append('<div class="tab-pane fade" id="question-' + no + '" role="tabpanel" aria-labelledby="question-' + no + '-tab"><div class="form-group"><label for="question">Soal <code>*</code></label><textarea id="question-text-' + no + '"></textarea></div><div class="form-group"><label for="question">Sintak Tes <code>*</code></label><textarea id="question-syntax-' + no + '"></textarea></div></div>');

                if (typeof cm[no] == 'undefined') {
                    cm[no] = CodeMirror.fromTextArea($('#forms #question-tabContent textarea#question-syntax-' + no)[0], {
                        lineNumbers: true,
                        styleActiveLine: true,
                        mode: "sql",
                        theme: "dracula",
                    });
                }

                if (typeof ck[no] == 'undefined') {
                    CKEDITOR.ClassicEditor.create($('#forms #question-tabContent textarea#question-text-' + no)[0], {
                        toolbar: {
                            items: [
                                'heading', '|',
                                'alignment', '|',
                                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                                'bold', 'italic', 'strikethrough', 'underline', 'code', 'removeFormat', '|',
                                'bulletedList', 'numberedList', 'todoList', '|',
                                'outdent', 'indent', '|',
                                'link', 'insertImage', 'blockQuote', 'insertTable', 'mediaEmbed', 'codeBlock', '|',
                                'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                                'sourceEditing'
                            ],
                            shouldNotGroupWhenFull: true
                        },
                        placeholder: '',
                        list: {
                            properties: {
                                styles: true,
                                startIndex: true,
                                reversed: true
                            }
                        },
                        heading: {
                            options: [{
                                    model: 'paragraph',
                                    title: 'Paragraph',
                                    class: 'ck-heading_paragraph'
                                },
                                {
                                    model: 'heading1',
                                    view: 'h1',
                                    title: 'Heading 1',
                                    class: 'ck-heading_heading1'
                                },
                                {
                                    model: 'heading2',
                                    view: 'h2',
                                    title: 'Heading 2',
                                    class: 'ck-heading_heading2'
                                },
                                {
                                    model: 'heading3',
                                    view: 'h3',
                                    title: 'Heading 3',
                                    class: 'ck-heading_heading3'
                                },
                                {
                                    model: 'heading4',
                                    view: 'h4',
                                    title: 'Heading 4',
                                    class: 'ck-heading_heading4'
                                },
                                {
                                    model: 'heading5',
                                    view: 'h5',
                                    title: 'Heading 5',
                                    class: 'ck-heading_heading5'
                                },
                                {
                                    model: 'heading6',
                                    view: 'h6',
                                    title: 'Heading 6',
                                    class: 'ck-heading_heading6'
                                }
                            ]
                        },
                        fontFamily: {
                            options: [
                                'default',
                                'Arial, Helvetica, sans-serif',
                                'Courier New, Courier, monospace',
                                'Georgia, serif',
                                'Lucida Sans Unicode, Lucida Grande, sans-serif',
                                'Tahoma, Geneva, sans-serif',
                                'Times New Roman, Times, serif',
                                'Trebuchet MS, Helvetica, sans-serif',
                                'Verdana, Geneva, sans-serif'
                            ],
                            supportAllValues: true
                        },
                        fontSize: {
                            options: [10, 12, 14, 'default', 18, 20, 22],
                            supportAllValues: true
                        },
                        htmlSupport: {
                            allow: [{
                                name: /.*/,
                                attributes: true,
                                classes: true,
                                styles: true
                            }]
                        },
                        htmlEmbed: {
                            showPreviews: true
                        },
                        link: {
                            decorators: {
                                addTargetToExternalLinks: true,
                                defaultProtocol: 'https://',
                                toggleDownloadable: {
                                    mode: 'manual',
                                    label: 'Downloadable',
                                    attributes: {
                                        download: 'file'
                                    }
                                }
                            }
                        },
                        removePlugins: [
                            'CKBox',
                            'CKFinder',
                            'EasyImage',
                            'RealTimeCollaborativeComments',
                            'RealTimeCollaborativeTrackChanges',
                            'RealTimeCollaborativeRevisionHistory',
                            'PresenceList',
                            'Comments',
                            'TrackChanges',
                            'TrackChangesData',
                            'RevisionHistory',
                            'Pagination',
                            'WProofreader',
                            'MathType'
                        ]
                    }).then(e => {
                        ck[no] = e;
                    }).then(() => {
                        $('#forms button#question-add').removeAttr('disabled');
                        $('#forms div.tab-pane').removeClass('active show');
                        $('#forms a#question-' + no + '-tab').trigger('click');
                        cm[no].refresh();
                        no++;
                    });
                }
            });

            $('#forms input,#forms select').unbind('click');
            $('#forms input,#forms select').on('click', function() {
                $(this).removeClass('is-invalid');
            });
        }

        const formSubmit = () => {
            let url;
            switch ($('#formModalLabel').html()) {
                case 'Tambah Modul Praktek':
                    url = '{{ route('admin sql practice create') }}'
                    break;
                case 'Ubah Modul Praktek':
                    let id = $('form button[type=submit]').attr('data-id');
                    url = '{{ route('admin sql practice update', ':id') }}'
                    url = url.replace(':id', id);
                    break;

                default:
                    break;
            }

            $('#forms').unbind('submit');
            $('#forms').on('submit', function() {
                let formData = new FormData($('#forms')[0]);
                for (let i = 1; i < no; i++) {
                    console.log(i);
                    formData.append('question[]', JSON.stringify({
                        'question': ck[i].getData(),
                        'syntax': cm[i].getValue()
                    }));
                }
                if (url) {
                    let elm = $(this);
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        beforeSend: function() {
                            elm.find('button[type=submit]').html('Tunggu sebentar');
                            elm.find('button').attr('disabled', true);
                            elm.find(':input').attr('disabled', true)
                        },
                        complete: function() {
                            elm.find('button[type=submit]').html('Simpan');
                            elm.find('button').removeAttr('disabled');
                            elm.find(':input').removeAttr('disabled')
                        },
                        error: function() {
                            elm.find('button[type=submit]').html('Simpan');
                            elm.find('button').removeAttr('disabled');
                            elm.find(':input').removeAttr('disabled')
                        },
                        success: function(response) {
                            console.log(response);
                            if (response != 'ok') {
                                let html = '';
                                $.each(response, function(index, value) {
                                    html += '<small class="badge badge-danger d-block text-left my-1"><i class="fa fa-times px-1 mr-1"></i> <span class="border-left px-2">' + value + '</span></small>';
                                    $('input[name=' + index + ']').addClass('is-invalid');
                                });
                                $('form #error-message').html(html)
                                $('form #error-message').addClass('d-block');
                                $('form #error-message').removeClass('d-none');
                            } else {
                                $('#formModal').modal('hide');
                                Swal.fire({
                                    title: $('#formModalLabel').html().replace(' Modul Praktek', '') + ' Data Berhasil',
                                    text: "data berhasil di" + $('#formModalLabel').html().replace(' Modul Praktek', '').toLowerCase() + " kedalam database",
                                    type: 'success',
                                    confirmButtonText: 'Mantap!'
                                }).then(() => {
                                    loads();
                                })
                            }
                        }
                    });
                }
            });
        }
    </script>
@endsection
@section('content')
    <div class="row" id="content">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Modul Praktek SQL</h3>
                    <div class="card-tools">
                        <button class="btn btn-tool" data-action="reload">
                            <i class="fa fa-sync"></i>
                            &nbsp; Refresh
                        </button>
                        <button class="btn btn-tool" data-action="create">
                            <i class="fa fa-plus"></i>
                            &nbsp; Add
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr class="text-center">
                                        <th>NO</th>
                                        <th>Name</th>
                                        <th>Jumlah Soal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="javascript:void(0)" enctype="multipart/form-data" id="forms">
        <div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="formModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="formModalLabel"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4 d-none" id="error-message"></div>
                        <div class="form-group">
                            <label for="name">Nama Modul <code>*</code></label>
                            <input type="text" class="form-control" name="name" id="name" autocomplete="off">
                        </div>
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Data Soal</h3>
                                <div class="card-tools">
                                    <button type="button" id="question-add" class="btn btn-tool"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 mb-2 col-md-3 col-lg-2">
                                        <div class="nav flex-column nav-tabs h-100" id="question-tab" role="tablist" aria-orientation="vertical">
                                        </div>
                                    </div>
                                    <div class="col-12 mb-2 col-md-9 col-lg-10">
                                        <div class="tab-content" id="question-tabContent">
                                            {{-- <div class="form-group">
                                                <label for="question">Soal '+i+' <code>*</code></label>
                                                <textarea name="question-syntax['+i+']" id="question-syntax['+i+']"></textarea>
                                            </div> --}}
                                            {{-- <div class="tab-pane text-left fade show active" id="vert-tabs-home" role="tabpanel" aria-labelledby="vert-tabs-home-tab">
                                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin malesuada lacus ullamcorper dui molestie, sit amet congue quam finibus. Etiam ultricies nunc non magna feugiat commodo. Etiam odio magna, mollis auctor felis vitae, ullamcorper ornare ligula. Proin pellentesque tincidunt nisi, vitae ullamcorper felis aliquam id. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Proin id orci eu lectus blandit suscipit. Phasellus porta, ante et varius ornare, sem enim sollicitudin eros, at commodo leo est vitae lacus. Etiam ut porta sem. Proin porttitor porta nisl, id tempor risus rhoncus quis. In in quam a nibh cursus pulvinar non consequat neque. Mauris lacus elit, condimentum ac condimentum at, semper vitae lectus. Cras lacinia erat eget sapien porta consectetur.
                                            </div>
                                            <div class="tab-pane fade" id="vert-tabs-profile" role="tabpanel" aria-labelledby="vert-tabs-profile-tab">
                                                Mauris tincidunt mi at erat gravida, eget tristique urna bibendum. Mauris pharetra purus ut ligula tempor, et vulputate metus facilisis. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Maecenas sollicitudin, nisi a luctus interdum, nisl ligula placerat mi, quis posuere purus ligula eu lectus. Donec nunc tellus, elementum sit amet ultricies at, posuere nec nunc. Nunc euismod pellentesque diam.
                                            </div> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
