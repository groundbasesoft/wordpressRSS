(function($){
    $(document).ready(function(){
        $('#a2zfa-add-row').on('click', function(){
            const $tbody = $('#a2zfa-sources tbody');
            const index = $tbody.find('tr').length;
            const row = `<tr>
                <td><input type="checkbox" name="sources[${index}][enabled]" checked></td>
                <td>
                    <select name="sources[${index}][type]">
                        <option value="rss" selected>RSS/Atom</option>
                        <option value="json">JSON</option>
                    </select>
                </td>
                <td><input type="text" class="regular-text" name="sources[${index}][label]" value=""></td>
                <td><input type="url" class="regular-text code" name="sources[${index}][url]" value=""></td>
                <td><button class="button a2zfa-remove-row" type="button">Remove</button></td>
            </tr>`;
            $tbody.append(row);
        });

        $(document).on('click', '.a2zfa-remove-row', function(){
            $(this).closest('tr').remove();
        });
    });
})(jQuery);
