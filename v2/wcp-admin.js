jQuery(document).ready(function($) {
    // Add new price row (Customer Details)
    $('.wcp-add-price').on('click', function() {
        const table = $(this).closest('.wcp-pricing-table');
        const userId = table.data('user-id');
        const template = `
            <tr data-user-id="${userId}" data-index="${Date.now()}">
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

    // Save row (Customer Details)
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
                    statusCell.html(productId && price ? '<span class="wcp-checkmark">✔</span>' : '');
                    alert(response.data.message);
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('AJAX error occurred.');
            }
        });
    });

    // Delete row (Customer Details)
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
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('AJAX error occurred.');
                }
            });
        }
    });

    // Customer search
    let searchTimeout;
    $('#wcp-customer-search').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        searchTimeout = setTimeout(function() {
            wcp_search_customers(searchTerm, 1);
        }, 300);
    });

    $(document).on('click', '.tablenav-pages a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const paged = href.match(/paged=(\d+)/) ? parseInt(href.match(/paged=(\d+)/)[1]) : 1;
        const searchTerm = $('#wcp-customer-search').val();
        wcp_search_customers(searchTerm, paged);
    });

    function wcp_search_customers(search, paged) {
        $.ajax({
            url: wcp_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wcp_search_customers',
                nonce: wcp_ajax.nonce,
                search: search,
                paged: paged
            },
            success: function(response) {
                if (response.success) {
                    $('#wcp-customer-table tbody').html(response.data.table_content);
                    $('.tablenav-pages').html(response.data.pagination + ' <span class="displaying-num">' + response.data.total_users + ' customers</span>');
                } else {
                    alert('Error searching customers.');
                }
            },
            error: function() {
                alert('AJAX error occurred.');
            }
        });
    }

    // Save rule assignment (Customer List)
    $(document).on('click', '.wcp-save-rule-assignment', function() {
        const row = $(this).closest('tr');
        const userId = row.data('user-id');
        const ruleId = row.find('.wcp-rule-select').val();
        const statusCell = row.find('.wcp-status');

        $.ajax({
            url: wcp_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wcp_save_rule_assignment',
                nonce: wcp_ajax.nonce,
                user_id: userId,
                rule_id: ruleId
            },
            success: function(response) {
                if (response.success) {
                    statusCell.html(response.data.status ? '<span class="wcp-checkmark">✔</span>' : '');
                    alert(response.data.message);
                } else {
                    alert('Error saving rule assignment.');
                }
            },
            error: function() {
                alert('AJAX error occurred.');
            }
        });
    });

    // Add new rule
    $('.wcp-add-rule').on('click', function(e) {
        e.preventDefault();
        const newRuleId = Date.now().toString(); // Ensure string for consistency
        const newUrl = wcp_ajax.admin_url + '&tab=pricing-rules&rule_id=' + encodeURIComponent(newRuleId);
        window.location.href = newUrl; // Direct redirect
    });

    // Add new price row (Pricing Rules)
    $('.wcp-add-rule-price').on('click', function() {
        const table = $(this).closest('.wcp-rule-pricing-table');
        const ruleId = table.data('rule-id');
        const template = `
            <tr data-rule-id="${ruleId}" data-index="${Date.now()}">
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
                    <button type="button" class="button wcp-save-rule-row">Save</button>
                    <button type="button" class="button wcp-delete-rule-row">Delete</button>
                </td>
            </tr>`;
        $(this).closest('tr').before(template);
    });

    // Save rule row
    $(document).on('click', '.wcp-save-rule-row', function() {
        const row = $(this).closest('tr');
        const ruleId = row.data('rule-id');
        const index = row.data('index');
        const productId = row.find('.wcp-product-select').val();
        const price = row.find('.wcp-price-input').val();
        const statusCell = row.find('.wcp-status');
        const ruleName = $('#wcp-rule-name').val();

        $.ajax({
            url: wcp_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wcp_save_rule_price',
                nonce: wcp_ajax.nonce,
                rule_id: ruleId,
                index: index,
                product_id: productId,
                price: price,
                rule_name: ruleName
            },
            success: function(response) {
                if (response.success) {
                    statusCell.html(productId && price ? '<span class="wcp-checkmark">✔</span>' : '');
                    alert(response.data.message);
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('AJAX error occurred.');
            }
        });
    });

    // Delete rule row
    $(document).on('click', '.wcp-delete-rule-row', function() {
        const row = $(this).closest('tr');
        const ruleId = row.data('rule-id');
        const index = row.data('index');
        const ruleName = $('#wcp-rule-name').val();

        if (confirm('Are you sure you want to delete this price?')) {
            $.ajax({
                url: wcp_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'wcp_save_rule_price',
                    nonce: wcp_ajax.nonce,
                    rule_id: ruleId,
                    index: index,
                    product_id: '',
                    price: '',
                    rule_name: ruleName
                },
                success: function(response) {
                    if (response.success) {
                        row.remove();
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('AJAX error occurred.');
                }
            });
        }
    });

    // Delete rule
    $(document).on('click', '.wcp-delete-rule', function() {
        const ruleId = $(this).data('rule-id');
        if (confirm('Are you sure you want to delete this rule?')) {
            $.ajax({
                url: wcp_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'wcp_delete_rule',
                    nonce: wcp_ajax.nonce,
                    rule_id: ruleId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = wcp_ajax.admin_url + '&tab=pricing-rules';
                    } else {
                        alert('Error deleting rule.');
                    }
                },
                error: function() {
                    alert('AJAX error occurred.');
                }
            });
        }
    });

    // Bulk customer assignment
    $(document).on('click', '.wcp-assign-bulk-customers', function() {
        const ruleId = $(this).data('rule-id');
        const ruleName = $(this).closest('tr').find('td:first').text();
        $('#wcp-bulk-rule-name').text(ruleName);
        $('#wcp-bulk-rule-id').val(ruleId);
        $('#wcp-bulk-assign-form').show();
    });

    $('.wcp-cancel-bulk-customers').on('click', function() {
        $('#wcp-bulk-assign-form').hide();
        $('#wcp-bulk-customers').val([]);
    });

    $('.wcp-save-bulk-customers').on('click', function() {
        const ruleId = $('#wcp-bulk-rule-id').val();
        const customerIds = $('#wcp-bulk-customers').val() || [];

        $.ajax({
            url: wcp_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'wcp_save_bulk_customers',
                nonce: wcp_ajax.nonce,
                rule_id: ruleId,
                customer_ids: customerIds
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert('Error saving bulk customers.');
                }
            },
            error: function() {
                alert('AJAX error occurred.');
            }
        });
    });
});