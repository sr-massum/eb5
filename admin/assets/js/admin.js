/**
 * Admin panel JavaScript
 */

$(document).ready(function() {
    // Toggle sidebar on small screens
    $('.navbar-toggler').on('click', function() {
        $('.sidebar').toggleClass('d-none d-md-block');
    });
    
    // Auto hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').alert('close');
    }, 5000);
    
    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    }
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const input = $($(this).data('toggle'));
        const type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });
    
    // File input preview
    $('.custom-file-input').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass('selected').html(fileName);
        
        // If this is an image input with preview
        const previewEl = $(this).data('preview');
        if (previewEl && this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewEl).attr('src', e.target.result).show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Initialize TinyMCE if available
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '.tinymce',
            height: 400,
            menubar: true,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | \
                     alignleft aligncenter alignright alignjustify | \
                     bullist numlist outdent indent | removeformat | help'
        });
    }
    
    // Ajax form submission
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const submitText = submitBtn.html();
        const statusEl = form.data('status-element');
        
        // Disable submit button and show loading
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        // If using TinyMCE, make sure to get content
        if (typeof tinymce !== 'undefined') {
            form.find('.tinymce').each(function() {
                const id = $(this).attr('id');
                if (tinymce.get(id)) {
                    $(this).val(tinymce.get(id).getContent());
                }
            });
        }
        
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                // Re-enable submit button
                submitBtn.html(submitText).prop('disabled', false);
                
                // Handle response
                if (response.success) {
                    if (statusEl) {
                        $(statusEl).html(`<div class="alert alert-success">${response.message}</div>`);
                    } else {
                        alert(response.message);
                    }
                    
                    // If redirect URL is provided
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                    
                    // If callback function is provided
                    if (form.data('success-callback')) {
                        window[form.data('success-callback')](response);
                    }
                } else {
                    if (statusEl) {
                        $(statusEl).html(`<div class="alert alert-danger">${response.message}</div>`);
                    } else {
                        alert(response.message || 'An error occurred');
                    }
                }
            },
            error: function(xhr, status, error) {
                // Re-enable submit button
                submitBtn.html(submitText).prop('disabled', false);
                
                // Show error message
                if (statusEl) {
                    $(statusEl).html(`<div class="alert alert-danger">An error occurred: ${error}</div>`);
                } else {
                    alert('An error occurred: ' + error);
                }
            }
        });
    });
    
    // Initialize color picker if available
    if (typeof Pickr !== 'undefined') {
        $('.color-picker').each(function() {
            const el = this;
            const defaultColor = $(el).data('default-color') || '#5D5CDE';
            
            const pickr = Pickr.create({
                el: el,
                theme: 'classic',
                default: $(el).val() || defaultColor,
                components: {
                    preview: true,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: true,
                        input: true,
                        save: true
                    }
                }
            });
            
            // Save color to hidden input when changed
            pickr.on('save', (color) => {
                const inputId = $(el).data('input');
                $('#' + inputId).val(color.toHEXA().toString());
                pickr.hide();
            });
        });
    }
    
    // Toggle sections based on select/radio/checkbox changes
    $('[data-toggle-section]').on('change', function() {
        const targetSection = $(this).data('toggle-section');
        const showOnValue = $(this).data('toggle-value');
        
        if ($(this).is(':checkbox')) {
            // For checkboxes
            if ($(this).is(':checked')) {
                $(targetSection).removeClass('d-none');
            } else {
                $(targetSection).addClass('d-none');
            }
        } else {
            // For selects and radios
            const currentValue = $(this).val();
            
            if (currentValue === showOnValue) {
                $(targetSection).removeClass('d-none');
            } else {
                $(targetSection).addClass('d-none');
            }
        }
    });
    
    // Trigger change to initialize toggle sections
    $('[data-toggle-section]').trigger('change');
    
    // Sortable lists if jQuery UI is available
    if ($.fn.sortable) {
        $('.sortable-list').sortable({
            handle: '.sort-handle',
            update: function(event, ui) {
                // If there's a callback function
                const callback = $(this).data('sort-callback');
                if (callback && typeof window[callback] === 'function') {
                    window[callback]($(this));
                }
            }
        });
    }
    
    // Tooltip initialization
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Data table initialization
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "<i class='fas fa-chevron-right'></i>",
                    previous: "<i class='fas fa-chevron-left'></i>"
                }
            }
        });
    }
});