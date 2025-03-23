jQuery(document).ready(function($) {
    // Add new row
    $('.wcp-add-price').on('click', function() {
        const table = $(this).closest('.wcp-pricing-table');
        const userId = table.data('user-id');
        const template = `
            <tr data-user-id="${userId}" data-index="${new Date().getTime()}">
                <td>
                    <select class="wcp-product-select" name="product_id">
                        <option value="">Select a product</option>
                        ${table.find('.wcp-product-select:first').html()}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" class="wcp-price-input" name="price" placeholder="Custom Price" style="width: 100px;" />
                </td>
                <td class="wcp-status" style="text-align: center;"></td>
                <td>
                    <button type="button" class="button wcp-save-row">Save</button>
                    <button type="button" class="button wcp-delete-row">Delete</button>
                </td>
            </tr>`;
        $(this).closest('tr').before(template);
    });

    // Save row
    $(document).on('click', '.wcp-save-row', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');
        const index = row.data('index');
        const productId = row.find('.wcp-product-select').val();
        const price = row.find('.wcp-price-input').val();
        const statusCell = row.find('.wcp-status');

        $.ajax({
            url: wcp_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wcp_save_price',
                nonce: wcp_ajax.nonce,
                user_id: userId,
                index: index,
                product_id: productId,
                price: price
            },
            success: function(response) {
                if (response.success) {
                    if (productId && price) {
                        statusCell.html('<span class="wcp-checkmark">✔</span>');
                    } else {
                        statusCell.html('');
                    }
                } else {
                    alert('Error saving price.');
                }
            },
            error: function() {
                alert('AJAX error occurred.');
            }
        });
    });

    // Delete row
    $(document).on('click', '.wcp-delete-row', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');
        const index = row.data('index');

        if (confirm('Are you sure you want to delete this price?')) {
            $.ajax({
                url: wcp_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'wcp_save_price',
                    nonce: wcp_ajax.nonce,
                    user_id: userId,
                    index: index,
                    product_id: '',
                    price: ''
                },
                success: function(response) {
                    if (response.success) {
                        row.remove();
                        alert('Price deleted successfully!');
                    }
                }
            });
        }
    });

    // Update Customer Details tab link dynamically (optional, for smoother UX)
    $('.wcp-tab-pane#tab-customer-list a.button').on('click', function(e) {
        $('#customer-details-tab').attr('href', $(this).attr('href'));
    });
});