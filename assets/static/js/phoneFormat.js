(function ($) {
  $.fn.phoneFormat = function () {
    $(this).on("input paste", function (e) {
      e.preventDefault();
      let inputValue = "";

      // Ambil teks dari paste atau ketikan biasa
      if (e.type === "paste") {
        inputValue = (
          e.originalEvent.clipboardData || window.clipboardData
        ).getData("text");
      } else {
        inputValue = $(this).val();
      }

      // Hapus semua karakter non-angka
      inputValue = inputValue.replace(/\D/g, "");

      // Format sesuai panjang nomor
      let formatted = "";

      if (inputValue.length <= 4) {
        formatted = inputValue; // 4 angka pertama
      } else if (inputValue.length <= 7) {
        formatted = inputValue.replace(/(\d{4})(\d{1,3})/, "$1 $2");
      } else if (inputValue.length <= 11) {
        formatted = inputValue.replace(/(\d{4})(\d{3})(\d{1,4})/, "$1 $2 $3");
      } else {
        // kalau lebih dari 11 angka, bagi per 4 digit saja
        formatted = inputValue.replace(/(\d{4})(?=\d)/g, "$1 ").trim();
      }

      $(this).val(formatted);
    });
  };
})(jQuery);

// contoh penggunaan:
$(document).ready(function () {
  $("#phone").phoneFormat();
});
