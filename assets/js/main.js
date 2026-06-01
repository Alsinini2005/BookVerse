function submitComment(book_id) {
    var comment = $('#commentBox').val().trim();

    if (comment === '') {
        $('#commentMsg').html('<div class="alert alert-warning py-1">Comment cannot be empty.</div>');
        return;
    }
    if (comment.length > 1000) {
        $('#commentMsg').html('<div class="alert alert-warning py-1">Comment must be under 1000 characters.</div>');
        return;
    }

    $('#commentMsg').html('<span class="text-muted">Posting…</span>');

    $.ajax({
        url: BASE_URL + '/ajax/add-comment.php',
        method: 'POST',
        data: { book_id: book_id, comment_text: comment },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                $('#commentBox').val('');
                $('#commentMsg').html('<div class="alert alert-success py-1">' + response.message + '</div>');

                $('#noComments').remove();

                var html = '<div class="card mb-2">' +
                    '<div class="card-body py-2">' +
                    '<strong>' + response.name + '</strong>' +
                    '<small class="text-muted ms-2">' + response.created_at + '</small>' +
                    '<p class="mb-0 mt-1">' + response.comment.replace(/\n/g, '<br>') + '</p>' +
                    '</div></div>';
                $('#commentsList').prepend(html);

                setTimeout(function () { $('#commentMsg').html(''); }, 3000);
            } else {
                $('#commentMsg').html('<div class="alert alert-danger py-1">' + response.message + '</div>');
            }
        },
        error: function () {
            $('#commentMsg').html('<div class="alert alert-danger py-1">An error occurred. Please try again.</div>');
        }
    });
}
