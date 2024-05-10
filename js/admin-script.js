jQuery(document).ready(function ($) {
  // Event listener for changing tone selection
  $('input[type="radio"][name="openai_tone"]').change(function () {
    if ($("#custom").is(":checked")) {
      $("#custom_tone").show();
    } else {
      $("#custom_tone").hide();
    }
  });

  // Trigger the change event to set the initial visibility state
  $('input[type="radio"][name="openai_tone"]:checked').change();

  // Event listener for generate post button
  $("#generate-post").on("click", function () {
    var $btn = $(this);
    $btn.prop("disabled", true);

    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "openai_generate_post",
        _ajax_nonce: $("#abcc_openai_nonce").val(),
      },
      success: function (response) {
        alert(response.data);
      },
      error: function (xhr) {
        alert("Error generating post: " + xhr.responseText);
      },
      complete: function () {
        $btn.prop("disabled", false);
      },
    });
  });
});
