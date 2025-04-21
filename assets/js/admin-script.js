/**
 * WhatsApp Notify - Admin JavaScript
 */
(function ($) {
  "use strict";

  let formIsDirty = false; // Flag untuk menandakan perubahan belum disimpan
  let initialFormData = {}; // Untuk menyimpan data awal form
  let currentForm = null; // Form saat ini yang sedang diubah

  /**
   * WANotifyValidator - Objek untuk validasi input
   */
  const WANotifyValidator = {
    /**
     * Validasi nomor WhatsApp
     * @param {string} number Nomor yang akan divalidasi
     * @returns {object} Hasil validasi
     */
    validateWhatsAppNumber: function (number) {
      number = number.trim();
      const result = {
        isValid: false,
        message: "",
        formatted: number,
      };

      // Cek apakah kosong
      if (!number) {
        result.message = "Nomor WhatsApp tidak boleh kosong";
        return result;
      }

      // Validasi format - harus hanya berisi angka dan mungkin tanda + di awal
      if (!/^\+?[0-9]+$/.test(number)) {
        result.message =
          "Format nomor WhatsApp tidak valid. Hanya boleh berisi angka dan tanda + di awal";
        return result;
      }

      // Bersihkan dari karakter non-numerik kecuali + di awal (untuk keamanan ekstra)
      const cleanNumber = number.replace(/[^\d+]/g, "");

      // Cek panjang minimal
      const digitsOnly = cleanNumber.replace(/\+/g, "");
      if (digitsOnly.length < 10) {
        result.message = "Nomor WhatsApp terlalu pendek, minimal 10 digit";
        return result;
      }

      // Cek panjang maksimal
      if (digitsOnly.length > 15) {
        result.message = "Nomor WhatsApp terlalu panjang, maksimal 15 digit";
        return result;
      }

      // Format ulang untuk standarisasi
      let formattedNumber = cleanNumber;
      // Jika diawali 0, ganti dengan +62 (untuk Indonesia)
      if (formattedNumber.startsWith("0")) {
        formattedNumber = "+62" + formattedNumber.substring(1);
      }
      // Jika tidak diawali +, tambahkan +
      else if (!formattedNumber.startsWith("+")) {
        formattedNumber = "+" + formattedNumber;
      }

      result.isValid = true;
      result.formatted = formattedNumber;
      return result;
    },

    /**
     * Validasi URL API
     * @param {string} url URL yang akan divalidasi
     * @returns {object} Hasil validasi
     */
    validateApiUrl: function (url) {
      url = url.trim();
      const result = {
        isValid: false,
        message: "",
        formatted: url,
      };

      // Cek apakah kosong
      if (!url) {
        result.message = "URL API tidak boleh kosong";
        return result;
      }

      // Regex untuk validasi URL
      const urlPattern =
        /^(https?:\/\/)([\da-z.-]+)\.([a-z.]{2,6})([/\w.-]*)*\/?$/i;
      if (!urlPattern.test(url)) {
        result.message =
          "Format URL tidak valid. URL harus diawali dengan http:// atau https://";
        return result;
      }

      result.isValid = true;
      return result;
    },

    /**
     * Validasi template pesan
     * @param {string} template Template yang akan divalidasi
     * @returns {object} Hasil validasi
     */
    validateMessageTemplate: function (template) {
      template = template.trim();
      const result = {
        isValid: false,
        message: "",
        formatted: template,
        isWarning: false,
      };

      // Cek apakah kosong
      if (!template) {
        result.message = "Template pesan tidak boleh kosong";
        return result;
      }

      // Cek panjang minimal
      if (template.length < 10) {
        result.message = "Template pesan terlalu pendek, minimal 10 karakter";
        return result;
      }

      // Cek apakah memiliki minimal satu placeholder
      if (!template.includes("{") || !template.includes("}")) {
        result.message =
          "Template sebaiknya memiliki minimal satu placeholder seperti {form_name} atau {form_data}";
        // Ini hanya peringatan, bukan error fatal
        result.isWarning = true;
      }

      result.isValid = true;
      return result;
    },

    /**
     * Validasi token akses/autentikasi
     * @param {string} token Token yang akan divalidasi
     * @returns {object} Hasil validasi
     */
    validateAccessToken: function (token) {
      token = token.trim();
      const result = {
        isValid: false,
        message: "",
        formatted: token,
      };

      // Cek apakah kosong
      if (!token) {
        result.message = "Token autentikasi tidak boleh kosong";
        return result;
      }

      // Hanya validasi panjang minimum - hapus regex yang terlalu ketat
      if (token.length < 6) {
        result.message = "Token autentikasi terlalu pendek, minimal 6 karakter";
        return result;
      }

      result.isValid = true;
      return result;
    },
  };

  // DOM Ready
  $(function () {
    // Toggle field list based on "include all fields" checkbox
    toggleFieldList();

    // Initialize event listeners
    initEventListeners();

    // Initialize form handlers
    initFormHandlers();

    // Inisialisasi mode penerima
    initRecipientMode();

    // Inisialisasi mode penerima dinamis
    initDynamicRecipientMode();

    // Inisialisasi validasi mode penerima
    initRecipientValidation();

    // Simpan data awal form saat halaman dimuat
    saveInitialFormData();

    // Inisialisasi indikator status
    initDirtyStateIndicator();

    // Intersep navigasi
    interceptNavigation();

    // Inisialisasi toggle status formulir
    initFormStatusToggles();

    // Inisialisasi tombol "Kembali ke Daftar"
    initBackButtonHandler();

    // Inisialisasi halaman setelah dimuat
    initPageLoadHandlers();

    // inisialisasi validasi pengaturan umum
    initGeneralSettingsValidation();

    // Inisialisasi pengecekan konfigurasi
    initConfigChecker();
  });

  /**
   * Simpan data awal form - PERBAIKAN
   */
  function saveInitialFormData() {
    // Simpan data form pengaturan umum
    if ($("#wanotify-general-settings").length) {
      initialFormData["wanotify-general-settings"] = getFormState(
        "#wanotify-general-settings"
      );
    }

    // Simpan data form pengaturan formulir
    if ($("#wanotify-form-settings").length) {
      initialFormData["wanotify-form-settings"] = getFormState(
        "#wanotify-form-settings"
      );
    }
  }

  /**
   * Mendapatkan state form untuk perbandingan
   * Metode yang lebih akurat daripada serialize()
   */
  function getFormState(formSelector) {
    const $form = $(formSelector);
    const state = {};

    // Ambil nilai input text, textarea, dan select
    $form
      .find(
        'input[type="text"], input[type="url"], input[type="number"], textarea, select'
      )
      .each(function () {
        state[this.id || this.name] = $(this).val();
      });

    // Ambil nilai checkbox secara eksplisit
    $form.find('input[type="checkbox"]').each(function () {
      state[this.id || this.name] = $(this).is(":checked");
    });

    // Ambil nilai radio button yang checked
    $form.find('input[type="radio"]:checked').each(function () {
      state[this.name] = $(this).val();
    });

    return JSON.stringify(state);
  }

  /**
   * Deteksi perubahan form - PERBAIKAN
   */
  function checkFormDirty() {
    let isDirty = false;

    // Cek form pengaturan umum
    if ($("#wanotify-general-settings").length) {
      const currentState = getFormState("#wanotify-general-settings");
      if (initialFormData["wanotify-general-settings"] !== currentState) {
        isDirty = true;
        currentForm = document.getElementById("wanotify-general-settings");
      }
    }

    // Cek form pengaturan formulir
    if (!isDirty && $("#wanotify-form-settings").length) {
      const currentState = getFormState("#wanotify-form-settings");
      if (initialFormData["wanotify-form-settings"] !== currentState) {
        isDirty = true;
        currentForm = document.getElementById("wanotify-form-settings");
      }
    }

    // Update flag global
    formIsDirty = isDirty;
    updateDirtyStateIndicator();

    return isDirty;
  }

  /**
   * Toggle field list based on "include all fields" checkbox
   */
  function toggleFieldList() {
    const includeAllFields = $("#include_all_fields").is(":checked");

    if (includeAllFields) {
      $("#field_list").hide();
    } else {
      $("#field_list").show();
    }
  }

  /**
   * Initialize event listeners - PERBAIKAN
   */
  function initEventListeners() {
    // Toggle field list when "include all fields" changes
    $("#include_all_fields").on("change", function () {
      toggleFieldList();

      // Check/uncheck all fields when "include all fields" is checked
      if ($(this).is(":checked")) {
        $('#field_list input[type="checkbox"]').prop("checked", true);
      }
    });

    // Test connection button
    $("#wanotify-test-connection").on("click", testConnection);

    // Test form notification button
    $("#wanotify-test-form-notification").on("click", testFormNotification);

    // Clear logs button
    $("#wanotify-clear-logs").on("click", clearLogs);

    // Close notification
    $(document).on("click", ".wanotify-notification-close", function () {
      $(this)
        .closest(".wanotify-notification")
        .fadeOut(300, function () {
          $(this).remove();
        });
    });

    // Deteksi perubahan pada form - PERBAIKAN
    $("#wanotify-general-settings, #wanotify-form-settings").on(
      "change input keyup paste",
      function () {
        // Gunakan timeout untuk menghindari terlalu banyak pemeriksaan
        clearTimeout(window.wanotifyFormCheckTimer);
        window.wanotifyFormCheckTimer = setTimeout(function () {
          checkFormDirty();
        }, 100);
      }
    );

    // Tambahkan event khusus untuk tombol kembali ke daftar formulir
    $(".wanotify-back-btn").on("click", function (e) {
      // Periksa apakah ada perubahan yang belum disimpan
      if (formIsDirty) {
        if (
          !confirm(
            "Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin kembali?"
          )
        ) {
          e.preventDefault();
          return;
        }
      }

      // Reset form dirty state sebelum navigasi
      formIsDirty = false;
    });

    // PERBAIKAN: Update status tombol test sesuai status form
    $("#enabled").on("change", function() {
      updateTestButtonState($(this).is(":checked"));
    });
    
    // Panggil saat halaman dimuat
    setTimeout(function() {
      if ($("#enabled").length) {
        updateTestButtonState($("#enabled").is(":checked"));
      }
    }, 100);
  }

  /**
   * Update status tombol test berdasarkan status form
   * @param {boolean} isEnabled Status aktif form
   */
  function updateTestButtonState(isEnabled) {
    const $btn = $("#wanotify-test-form-notification");
    
    // Hapus tooltip container yang sudah ada (jika ada)
    $("#wanotify-test-tooltip").remove();
    
    if (!isEnabled) {
      $btn.addClass("button-disabled");
      
      // Buat tooltip yang lebih informatif dengan container khusus
      const $tooltipContainer = $('<span id="wanotify-test-tooltip" class="wanotify-tooltip-container"></span>');
      const $tooltipIcon = $('<span class="dashicons dashicons-info-outline"></span>');
      
      $tooltipContainer.attr('title', "Notifikasi harus diaktifkan untuk melakukan pengujian");
      $tooltipContainer.append($tooltipIcon);
      
      // Tambahkan tooltip setelah tombol
      $btn.after($tooltipContainer);
    } else {
      $btn.removeClass("button-disabled");
    }
  }

  /**
   * Initialize form handlers
   */
  function initFormHandlers() {
    // Form settings
    $("#wanotify-form-settings").on("submit", function (e) {
      e.preventDefault();

      // Jalankan validasi sebelum menyimpan
      if (!validateRecipientSettings()) {
        // Validasi gagal, jangan lanjutkan penyimpanan
        return false;
      }

      saveFormSettings($(this));
      initialFormData["wanotify-form-settings"] = getFormState(
        "#wanotify-form-settings"
      );
    });

    // Tambahkan event listener untuk real-time validation ketika nilai berubah
    $('input[name="recipient"]').on("input blur", function () {
      if ($('input[name="recipient_mode"]:checked').val() === "manual") {
        validateManualRecipient();
      }
    });
  }

  /**
   * Validasi pengaturan penerima
   * @returns {boolean} True jika valid, false jika tidak valid
   */
  function validateRecipientSettings() {
    // Cek mode yang dipilih
    const selectedMode = $('input[name="recipient_mode"]:checked').val();
    let isValid = true;

    // Reset semua pesan error
    $(".wanotify-field-error").remove();
    $(".wanotify-form-input").removeClass("has-error");
    $(".recipient-mode-settings").removeClass("has-error");

    if (selectedMode === "manual") {
      const customNumber = $('input[name="recipient"]').val().trim();

      // Gunakan WANotifyValidator untuk validasi format nomor
      const validationResult =
        WANotifyValidator.validateWhatsAppNumber(customNumber);

      if (!validationResult.isValid) {
        $('input[name="recipient"]').after(
          '<div class="wanotify-field-error">' +
            validationResult.message +
            "</div>"
        );
        $(".recipient-manual-settings").addClass("has-error");
        isValid = false;

        // Fokus ke field yang error
        $('input[name="recipient"]').focus();
      } else if (validationResult.formatted !== customNumber) {
        // Jika valid tapi perlu diformat ulang, update nilai field
        $('input[name="recipient"]').val(validationResult.formatted);
      }
    }

    if (selectedMode === "dynamic") {
      const selectedField = $('select[name="recipient_field"]').val();
      if (!selectedField || selectedField === "--" || selectedField === "") {
        $('select[name="recipient_field"]').after(
          '<div class="wanotify-field-error">Silakan pilih field</div>'
        );
        $(".recipient-dynamic-settings").addClass("has-error");
        isValid = false;

        // Fokus ke field yang error
        $('select[name="recipient_field"]').focus();
      }
    }

    if (!isValid) {
      // Tampilkan notifikasi global
      showNotification(
        "error",
        "Harap perbaiki error validasi sebelum menyimpan"
      );
    }

    return isValid;
  }

  /**
   * Save general settings via AJAX
   */
  function saveGeneralSettings($form) {
    const btnText = $form.find(".wanotify-submit-btn").text();
    $form
      .find(".wanotify-submit-btn")
      .text(wanotify.i18n.saving)
      .prop("disabled", true);

    // Hapus semua error yang ada sebelumnya
    $form.find(".wanotify-field-error").remove();
    $form.find(".wanotify-form-input").removeClass("has-error");

    // Format nomor WhatsApp sebelum mengirim
    const recipientValue = $form.find("#default_recipient").val();
    const recipientValidation =
      WANotifyValidator.validateWhatsAppNumber(recipientValue);
    const formattedRecipient = recipientValidation.isValid
      ? recipientValidation.formatted
      : recipientValue;

    const data = {
      action: "wanotify_save_settings",
      nonce: wanotify.nonce,
      api_url: $form.find("#api_url").val(),
      access_token: $form.find("#access_token").val(),
      default_recipient: formattedRecipient,
      default_template: $form.find("#default_template").val(),
      enable_logging: $form.find("#enable_logging").is(":checked"),
    };

    $.post(wanotify.ajax_url, data, function (response) {
      if (response.success) {
        showNotification("success", response.data.message);
        formIsDirty = false; // Reset flag setelah penyimpanan berhasil
        initialFormData["wanotify-general-settings"] = getFormState(
          "#wanotify-general-settings"
        ); // Update data awal
        updateDirtyStateIndicator();
      } else {
        // Tampilkan pesan error umum
        showNotification("error", response.data.message);
        
        // Tampilkan error per field jika ada
        if (response.data.errors) {
          displayFieldErrors($form, response.data.errors);
          
          // Fokus ke field error pertama
          $form.find(".wanotify-form-input.has-error")
            .first()
            .find("input, textarea, select")
            .focus();
        }
      }

      $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
    }).fail(function (xhr, status, error) {
      console.error("AJAX error:", status, error, xhr.responseText);
      showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
      $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
    });
  }

  /**
   * Tampilkan error per field dari response server
   * @param {jQuery} $form Form yang berisi field-field
   * @param {Object} errors Object berisi error per field (field_name: error_message)
   */
  function displayFieldErrors($form, errors) {
    // Loop melalui semua error
    for (const fieldName in errors) {
      if (errors.hasOwnProperty(fieldName)) {
        const errorMessage = errors[fieldName];
        const $field = $form.find(`#${fieldName}`);
        
        if ($field.length) {
          // Tambahkan kelas error pada container
          const $container = $field.closest(".wanotify-form-input");
          $container.addClass("has-error");
          
          // Tambahkan pesan error di bawah field
          $field.after('<div class="wanotify-field-error">' + errorMessage + '</div>');
          
          console.log(`Error pada field ${fieldName}: ${errorMessage}`);
        }
      }
    }
  }

  /**
   * Save form settings via AJAX - FIXED FOR AFTER TEST ISSUES
   */
  function saveFormSettings($form) {
    const formId = $form.data("form-id");
    const btnText = $form.find(".wanotify-submit-btn").text();

    $form
      .find(".wanotify-submit-btn")
      .text(wanotify.i18n.saving)
      .prop("disabled", true);

    // Tangkap nilai enabled secara eksplisit
    const isEnabled = $form.find("#enabled").is(":checked");
    
    // Debug info
    console.log(`Saving form ${formId} settings with enabled = ${isEnabled ? "1" : "0"}`);

    // Kumpulkan semua form data
    const formData = new FormData();
    formData.append('action', 'wanotify_save_form_settings');
    formData.append('nonce', wanotify.nonce);
    formData.append('form_id', formId);
    formData.append('enabled', isEnabled ? '1' : '0'); // Kirim sebagai string '1' atau '0'
    
    // PERBAIKAN: Tambahkan flag test_completed jika save dilakukan setelah test notification
    if (window.wanotifyTestCompleted) {
      formData.append('test_completed', 'true');
      console.log("Adding test_completed flag");
    }
    
    // Ambil recipient_mode, pilih nilai yang diseleksi
    const recipientMode = $form.find('input[name="recipient_mode"]:checked').val();
    formData.append('recipient_mode', recipientMode);
    
    formData.append('recipient', $form.find('input[name="recipient"]').val());
    formData.append('recipient_field', $form.find('select[name="recipient_field"]').val());
    formData.append('message_template', $form.find('#message_template').val());
    
    // Handle included fields
    if ($form.find('#include_all_fields').is(':checked')) {
        formData.append('included_fields[]', '*');
    } else {
        $form.find('input[name="included_fields[]"]:checked').each(function() {
            formData.append('included_fields[]', $(this).val());
        });
    }

    // Kirim request dengan FormData untuk menghindari masalah encoding
    $.ajax({
        url: wanotify.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log("Save form settings response:", response);

            if (response.success) {
                // PERBAIKAN: Pesan sukses yang lebih akurat
                let message = response.data.message;
                
                // Setelah save, reset flag test_completed
                window.wanotifyTestCompleted = false;
                
                showNotification("success", message);
                formIsDirty = false;

                // Simpan status untuk navigasi kembali
                window.wanotifyLastSavedStatus = {
                    formId: formId,
                    enabled: response.data.status,
                };

                // Simpan ke localStorage
                localStorage.setItem(
                    "wanotifyLastStatus",
                    JSON.stringify(window.wanotifyLastSavedStatus)
                );

                // Update data awal
                initialFormData["wanotify-form-settings"] = getFormState(
                    "#wanotify-form-settings"
                );
                updateDirtyStateIndicator();
            } else {
                // Tampilkan pesan error umum
                showNotification("error", response.data.message);
                
                // Tampilkan error per field jika ada
                if (response.data.errors) {
                    displayFieldErrors($form, response.data.errors);
                }
            }

            $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
        },
        error: function(xhr, status, error) {
            console.error("Save form settings failed:", status, error);
            console.log("Response text:", xhr.responseText);
            
            // PERBAIKAN: Log lebih detail untuk error
            try {
                const responseObj = JSON.parse(xhr.responseText);
                console.log("Parsed error response:", responseObj);
            } catch (e) {
                console.log("Could not parse error response");
            }
            
            showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
            $form.find(".wanotify-submit-btn").text(btnText).prop("disabled", false);
        }
    });
  }

  /**
   * Tambahkan event handler untuk tombol "Kembali ke Daftar" - PERBAIKAN LANJUTAN
   */
  function initBackButtonHandler() {
    $(".wanotify-back-btn").on("click", function (e) {
      // Mencegah navigasi default
      e.preventDefault();

      const href = $(this).attr("href");

      // Periksa apakah ada perubahan yang belum disimpan
      if (formIsDirty) {
        if (
          !confirm(
            "Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin kembali?"
          )
        ) {
          return;
        }
      }

      // Jika kita berada di halaman konfigurasi formulir
      if ($("#wanotify-form-settings").length) {
        const formId = $("#wanotify-form-settings").data("form-id");
        const isEnabled = $("#enabled").is(":checked");

        console.log(
          `Navigating back from form ${formId} config with enabled = ${isEnabled}`
        );

        // Simpan status terakhir yang kita lihat
        localStorage.setItem(
          "wanotifyLastStatus",
          JSON.stringify({
            formId: formId,
            enabled: isEnabled,
          })
        );
      }

      // Reset form dirty state
      formIsDirty = false;

      // Arahkan ke halaman tujuan
      window.location.href = href;
    });
  }

  /**
   * Inisialisasi dan refresh status toggle setelah halaman dimuat - PERBAIKAN
   */
  function initPageLoadHandlers() {
    console.log("Page load handlers initialized");

    // Refresh status toggle pada semua halaman plugin untuk memastikan konsistensi
    setTimeout(function () {
      refreshFormStatuses();
    }, 300);

    // PERBAIKAN: Cek apakah kita baru saja kembali dari halaman konfigurasi form
    if (sessionStorage.getItem("wanotifyBackToList") === "true") {
      console.log("Detected back navigation from form configuration");

      // Hapus flag
      sessionStorage.removeItem("wanotifyBackToList");

      // Tambahkan force refresh dengan delay lebih panjang
      setTimeout(function () {
        console.log("Performing forced refresh after back navigation");
        refreshFormStatuses();

        // Tambahkan refresh kedua untuk memastikan (fallback)
        setTimeout(refreshFormStatuses, 1000);
      }, 500);
    }
  }

  /**
   * Test WhatsApp connection
   */
  function testConnection() {
    const $btn = $("#wanotify-test-connection");
    const btnText = $btn.text();

    $btn.text(wanotify.i18n.testing).prop("disabled", true);

    const data = {
      action: "wanotify_test_connection",
      nonce: wanotify.nonce,
    };

    $.post(wanotify.ajax_url, data, function (response) {
      if (response.success) {
        showNotification("success", response.data.message);
      } else {
        showNotification("error", response.data.message);
      }

      $btn.text(btnText).prop("disabled", false);
    }).fail(function () {
      showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
      $btn.text(btnText).prop("disabled", false);
    });
  }

  /**
   * Test form notification - PERBAIKAN UNTUK FORM TIDAK AKTIF
   */
  function testFormNotification() {
    const formId = $("#wanotify-form-settings").data("form-id");
    const $btn = $("#wanotify-test-form-notification");
    const btnText = $btn.text();

    // PERBAIKAN: Cek status aktif form terlebih dahulu
    const isEnabled = $("#enabled").is(":checked");
    if (!isEnabled) {
      showNotification(
        "error",
        "Notifikasi tidak diaktifkan untuk formulir ini. Aktifkan terlebih dahulu untuk melakukan pengujian."
      );
      return;
    }

    // Ambil mode penerima yang dipilih
    const selectedMode = $('input[name="recipient_mode"]:checked').val();

    // Validasi mode penerima
    if (
      selectedMode === "manual" &&
      $('input[name="recipient"]').val().trim() === ""
    ) {
      showNotification(
        "error",
        "Nomor kustom wajib diisi untuk mengirim notifikasi tes."
      );
      return;
    }

    if (
      selectedMode === "dynamic" &&
      ($('select[name="recipient_field"]').val() === "" ||
        $('select[name="recipient_field"]').val() === "--")
    ) {
      showNotification("error", "Silakan pilih field terlebih dahulu.");
      return;
    }

    $btn.text(wanotify.i18n.testing).prop("disabled", true);

    // Kirim mode penerima yang dipilih
    const data = {
      action: "wanotify_test_form_notification",
      nonce: wanotify.nonce,
      form_id: formId,
      recipient_mode: selectedMode, // Tambah parameter mode penerima
    };

    $.post(wanotify.ajax_url, data, function (response) {
      if (response.success) {
        showNotification("success", response.data.message);
        
        // PERBAIKAN: Simpan state form saat ini sebagai initial data untuk mencegah false "dirty state"
        // setelah test notification
        initialFormData["wanotify-form-settings"] = getFormState(
          "#wanotify-form-settings"
        );
        
        // Reset flag dirty karena kita sudah meng-update initial state
        formIsDirty = false;
        updateDirtyStateIndicator();
        
        // Set flag bahwa test selesai untuk dikirim saat save form
        window.wanotifyTestCompleted = true;
      } else {
        showNotification("error", response.data.message);
      }

      $btn.text(btnText).prop("disabled", false);
    }).fail(function (xhr, status, error) {
      console.error("Test form notification failed:", status, error);
      console.log("Response text:", xhr.responseText);
      
      // PERBAIKAN: Tambahkan pencatatan error detail
      try {
        const responseObj = JSON.parse(xhr.responseText);
        console.log("Parsed error response:", responseObj);
      } catch (e) {
        console.log("Could not parse error response");
      }
      
      showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
      $btn.text(btnText).prop("disabled", false);
    });
  }

  /**
   * Clear logs
   */
  function clearLogs() {
    if (!confirm(wanotify.i18n.confirm_clear_logs)) {
      return;
    }

    const $btn = $("#wanotify-clear-logs");
    const btnText = $btn.text();

    $btn.text("Menghapus...").prop("disabled", true);

    const data = {
      action: "wanotify_clear_logs",
      nonce: wanotify.nonce,
    };

    $.post(wanotify.ajax_url, data, function (response) {
      if (response.success) {
        showNotification("success", response.data.message);
        $("#wanotify-logs-container").html("<p>Log telah dibersihkan.</p>");
      } else {
        showNotification("error", response.data.message);
      }

      $btn.text(btnText).prop("disabled", false);
    }).fail(function () {
      showNotification("error", "Terjadi kesalahan server. Silakan coba lagi.");
      $btn.text(btnText).prop("disabled", false);
    });
  }

  /**
   * Show notification
   */
  function showNotification(type, message) {
    const icon = type === "success" ? "dashicons-yes-alt" : "dashicons-warning";
    const notificationHtml = `
            <div class="wanotify-notification wanotify-${type}">
                <div class="wanotify-notification-icon">
                    <span class="dashicons ${icon}"></span>
                </div>
                <div class="wanotify-notification-message">${message}</div>
                <div class="wanotify-notification-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </div>
            </div>
        `;

    $("#wanotify-notification-area").append(notificationHtml);

    // Auto-hide after 5 seconds
    setTimeout(function () {
      $(".wanotify-notification")
        .first()
        .fadeOut(300, function () {
          $(this).remove();
        });
    }, 5000);
  }

  /**
   * Inisialisasi mode penerima
   */
  function initRecipientMode() {
    // Toggle recipient mode
    $(".recipient-mode-selector").on("change", function () {
      var selectedMode = $('input[name="recipient_mode"]:checked').val();

      // Sembunyikan semua pengaturan
      $(".recipient-mode-settings").hide();

      // Tampilkan pengaturan untuk mode yang dipilih
      if (selectedMode === "manual") {
        $(".recipient-manual-settings").show();
      } else if (selectedMode === "dynamic") {
        $(".recipient-dynamic-settings").show();
      }
    });

    // Trigger perubahan saat halaman dimuat
    $(".recipient-mode-selector:checked").trigger("change");
  }

  /**
   * Intercept navigation
   */
  function interceptNavigation() {
    // Intercept beforeunload event
    $(window).on("beforeunload", function (e) {
      if (formIsDirty) {
        // Standard for most browsers
        e.returnValue =
          "Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?";
        return "Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?"; // For some older browsers
      }
    });
  }

  /**
   * Inisialisasi indikator status
   */
  function initDirtyStateIndicator() {
    // Tambahkan elemen indikator ke header
    $(".wanotify-header").append(
      '<span id="wanotify-dirty-state" class="wanotify-dirty-state"></span>'
    );
    updateDirtyStateIndicator();
  }

  /**
   * Perbarui indikator status
   */
  function updateDirtyStateIndicator() {
    if (formIsDirty) {
      $("#wanotify-dirty-state")
        .text("Perubahan Belum Disimpan")
        .addClass("active");
    } else {
      $("#wanotify-dirty-state").text("").removeClass("active");
    }
  }

  /**
   * Inisialisasi toggle status formulir - PERBAIKAN UTAMA
   */
  function initFormStatusToggles() {
    $(document).on("change", ".wanotify-status-toggle", function () {
      const $toggle = $(this);
      const formId = $toggle.data("form-id");
      const isEnabled = $toggle.is(":checked");
      const $statusText = $toggle.siblings(".wanotify-toggle-status");
      const $toggleContainer = $toggle.closest(".wanotify-toggle");

      // Simpan status awal untuk rollback jika gagal
      const originalStatus = !isEnabled;

      // Tambahkan kelas loading
      $toggleContainer.addClass("wanotify-loading");

      // Update text sementara
      $statusText.text(
        isEnabled
          ? wanotify.i18n.activating || "Mengaktifkan..."
          : wanotify.i18n.deactivating || "Menonaktifkan..."
      );

      // Kirim AJAX request
      $.post(wanotify.ajax_url, {
        action: "wanotify_toggle_form_status",
        nonce: wanotify.nonce,
        form_id: formId,
        enabled: isEnabled,
      })
        .done(function (response) {
          if (response.success) {
            // Gunakan status dari respons server untuk memastikan data yang benar
            const newStatus =
              response.data.status !== undefined
                ? response.data.status
                : isEnabled;

            // Update status text sesuai dengan status yang dikembalikan server
            $statusText.text(newStatus ? "Aktif" : "Tidak Aktif");

            // Pastikan checkbox sesuai dengan status aktual
            $toggle.prop("checked", newStatus);

            // Update form settings jika halaman konfigurasi formulir terbuka
            updateFormSettingsAfterToggle(formId, newStatus);

            showNotification("success", response.data.message);
          } else {
            // Kembalikan toggle ke status sebelumnya
            $toggle.prop("checked", originalStatus);
            $statusText.text(originalStatus ? "Aktif" : "Tidak Aktif");
            showNotification("error", response.data.message);
          }
        })
        .fail(function () {
          // Kembalikan toggle ke status sebelumnya
          $toggle.prop("checked", originalStatus);
          $statusText.text(originalStatus ? "Aktif" : "Tidak Aktif");
          showNotification(
            "error",
            "Terjadi kesalahan server. Silakan coba lagi."
          );
        })
        .always(function () {
          // Hapus kelas loading
          $toggleContainer.removeClass("wanotify-loading");
        });
    });

    // Load status aktual setelah halaman dimuat (memastikan konsistensi)
    refreshFormStatuses();
  }

  /**
   * Refresh status formulir dari server - PERBAIKAN
   */
  function refreshFormStatuses() {
    // Hanya lakukan jika ada toggle status di halaman
    if ($(".wanotify-status-toggle").length === 0) {
      console.log("No form status toggles found on page");
      return;
    }

    // Kumpulkan semua ID formulir di halaman
    const formIds = [];
    $(".wanotify-status-toggle").each(function () {
      formIds.push($(this).data("form-id"));
    });

    if (formIds.length === 0) {
      console.log("No form IDs collected");
      return;
    }

    console.log("Refreshing form statuses for IDs:", formIds);

    // Ambil status terbaru dari server
    $.post(wanotify.ajax_url, {
      action: "wanotify_get_forms_status",
      nonce: wanotify.nonce,
      form_ids: formIds,
    })
      .done(function (response) {
        console.log("Get forms status response:", response);

        if (response.success && response.data.statuses) {
          // Update semua toggle berdasarkan status dari server
          $.each(response.data.statuses, function (formId, status) {
            console.log(
              `Updating form ${formId} status to ${
                status ? "active" : "inactive"
              }`
            );

            const $toggle = $(
              `.wanotify-status-toggle[data-form-id="${formId}"]`
            );
            if ($toggle.length) {
              $toggle.prop("checked", status);
              $toggle
                .siblings(".wanotify-toggle-status")
                .text(status ? "Aktif" : "Tidak Aktif");
              console.log(`Updated UI for form ${formId}`);
            } else {
              console.log(`Toggle for form ${formId} not found in DOM`);
            }
          });
        } else {
          console.error("Error in response or missing statuses:", response);
        }
      })
      .fail(function (xhr, status, error) {
        console.error("AJAX request failed:", status, error);
      })
      .always(function () {
        // Cek localStorage untuk status terakhir yang disimpan
        const lastStatusJson = localStorage.getItem("wanotifyLastStatus");
        if (lastStatusJson) {
          try {
            const lastStatus = JSON.parse(lastStatusJson);
            console.log("Found last saved status in localStorage:", lastStatus);

            // Terapkan status yang disimpan di localStorage jika form ada di halaman
            const $toggle = $(
              `.wanotify-status-toggle[data-form-id="${lastStatus.formId}"]`
            );
            if ($toggle.length) {
              console.log(
                `Applying saved status (${
                  lastStatus.enabled ? "active" : "inactive"
                }) to form ${lastStatus.formId} from localStorage`
              );
              $toggle.prop("checked", lastStatus.enabled);
              $toggle
                .siblings(".wanotify-toggle-status")
                .text(lastStatus.enabled ? "Aktif" : "Tidak Aktif");
            }

            // Hapus item dari localStorage setelah digunakan
            localStorage.removeItem("wanotifyLastStatus");
          } catch (e) {
            console.error("Error parsing last status from localStorage:", e);
          }
        }
      });
  }

  /**
   * Perbarui pengaturan form setelah toggle status - PERBAIKAN
   */
  function updateFormSettingsAfterToggle(formId, enabled) {
    // Cek apakah halaman konfigurasi formulir dengan ID ini sedang terbuka
    const $formSettings = $("#wanotify-form-settings");

    if (
      $formSettings.length &&
      parseInt($formSettings.data("form-id")) === parseInt(formId)
    ) {
      // Update checkbox "enabled" tanpa memicu event change
      const $enabledCheckbox = $("#enabled");
      if ($enabledCheckbox.length) {
        $enabledCheckbox.prop("checked", enabled);
      }

      // Perbarui initialFormData untuk mencegah false detection "unsaved changes"
      initialFormData["wanotify-form-settings"] = getFormState(
        "#wanotify-form-settings"
      );

      // Reset flag dirty karena perubahan sudah disimpan
      formIsDirty = false;
      updateDirtyStateIndicator();
    }
  }

  /**
   * Inisialisasi handler untuk mode penerima dinamis yang mencegah "dirty state" pada penyesuaian otomatis
   */
  function initDynamicRecipientMode() {
    // Menangani kasus ketika field telepon tidak tersedia
    $(".wanotify-radio-disabled").on("click", function (e) {
      // Jika radio button dalam container ini, cegah pemilihan
      if ($(this).find('input[type="radio"]').is(":disabled")) {
        e.preventDefault();

        // Tampilkan pesan toast notification
        showNotification(
          "info",
          wanotify.i18n.phone_field_required ||
            "Opsi ini tidak tersedia karena tidak ada field telepon di formulir"
        );

        return false;
      }
    });

    // Cek apakah terjadi kondisi dimana dynamic mode terpilih tetapi tidak valid
    let needsAutoAdjustment = false;

    // Jika opsi dynamic terpilih tetapi dinonaktifkan (tidak ada field telepon)
    if ($('.wanotify-radio-disabled input[type="radio"]:checked').length) {
      needsAutoAdjustment = true;
    }

    // Jika perlu penyesuaian otomatis
    if (needsAutoAdjustment) {
      // Simpan state form sebelum perubahan
      const formId = $("#wanotify-form-settings").data("form-id");

      // Beralih ke "default" jika dynamic terpilih tetapi dinonaktifkan
      $('input[name="recipient_mode"][value="default"]').prop("checked", true);

      // Update UI dengan silent change (tanpa memicu event change)
      updateRecipientModeUI($('input[name="recipient_mode"]:checked').val());

      // Tambahkan notifikasi visual langsung, tidak menunggu AJAX response
      showNotification(
        "info",
        wanotify.i18n.settings_auto_adjusted ||
          "Pengaturan disesuaikan otomatis karena field telepon tidak tersedia lagi"
      );

      // Simpan perubahan secara otomatis tanpa menandai form sebagai dirty
      saveAutoAdjustedSettings(formId);
    }
  }

  /**
   * Menyimpan pengaturan yang otomatis disesuaikan
   */
  function saveAutoAdjustedSettings(formId) {
    // Ambil nilai form saat ini
    const data = {
      action: "wanotify_auto_adjust_form_settings",
      nonce: wanotify.nonce,
      form_id: formId,
      enabled: $("#enabled").is(":checked") ? "1" : "0",
      recipient_mode: "default", // Kita paksa ke default
      recipient: $('input[name="recipient"]').val(),
      recipient_field: $('select[name="recipient_field"]').val(),
      message_template: $("#message_template").val(),
    };

    // Handle included fields
    if ($("#include_all_fields").is(":checked")) {
      data.included_fields = ["*"];
    } else {
      data.included_fields = [];
      $('input[name="included_fields[]"]:checked').each(function () {
        data.included_fields.push($(this).val());
      });
    }

    // Post dengan quiet save (tanpa UI feedback)
    $.post(wanotify.ajax_url, data, function (response) {
      if (response.success) {
        console.log("Form settings auto-adjusted successfully");

        // Tampilkan notifikasi kecil
        showNotification(
          "info",
          wanotify.i18n.settings_auto_adjusted ||
            "Pengaturan disesuaikan otomatis karena field telepon tidak tersedia"
        );

        // Yang paling penting: refresh state awal form agar form tidak terdeteksi "dirty"
        initialFormData["wanotify-form-settings"] = getFormState(
          "#wanotify-form-settings"
        );
        formIsDirty = false;
        updateDirtyStateIndicator();
      }
    });
  }

  /**
   * Update UI untuk mode penerima tanpa trigger perubahan
   */
  function updateRecipientModeUI(selectedMode) {
    // Sembunyikan semua panel pengaturan
    $(".recipient-mode-settings").hide();

    // Tampilkan panel yang sesuai dengan mode yang dipilih
    $(`.recipient-${selectedMode}-settings`).show();
  }

  /**
   * Inisialisasi validasi mode penerima - FIXED
   */
  function initRecipientValidation() {
    // Tambahkan validasi saat form disubmit
    // (Sudah dihandle oleh validateRecipientSettings di initFormHandlers)

    // Tambahkan validasi pada perubahan mode
    $(".recipient-mode-selector").on("change", function () {
      const selectedMode = $('input[name="recipient_mode"]:checked').val();

      // Reset semua pesan error dan kelas has-error
      $(".wanotify-field-error").remove();
      $(".wanotify-form-input").removeClass("has-error");
      $(".recipient-mode-settings").removeClass("has-error");

      if (selectedMode === "manual") {
        validateManualRecipient();
      }

      if (selectedMode === "dynamic") {
        validateDynamicRecipient();
      }
    });

    // Validasi real-time pada field nomor kustom
    $('input[name="recipient"]').on("change keyup", function () {
      if ($('input[name="recipient_mode"]:checked').val() === "manual") {
        validateManualRecipient();
      }
    });

    // Validasi real-time pada select field
    $('select[name="recipient_field"]').on("change", function () {
      if ($('input[name="recipient_mode"]:checked').val() === "dynamic") {
        validateDynamicRecipient();
      }
    });

    // Validasi tes notifikasi sebelum mengirim
    $("#wanotify-test-form-notification").on("click", function (e) {
      if (!validateRecipientSettings()) {
        e.preventDefault();
        return false;
      }
    });
  }

  /**
   * Validasi untuk nomor kustom (manual)
   */
  function validateManualRecipient() {
    const $input = $('input[name="recipient"]');
    const $container = $(".recipient-manual-settings");

    // Hapus error sebelumnya
    $(".wanotify-field-error", $container).remove();
    $container.removeClass("has-error");

    // Gunakan validator WhatsApp number
    const result = WANotifyValidator.validateWhatsAppNumber($input.val());

    if (!result.isValid) {
      $input.after(
        '<div class="wanotify-field-error">' + result.message + "</div>"
      );
      $container.addClass("has-error");
      return false;
    }

    // Jika valid dan nomor terformat ulang, update field
    if (result.formatted !== $input.val()) {
      $input.val(result.formatted);
    }

    return true;
  }

  /**
   * Validasi untuk field dinamis
   */
  function validateDynamicRecipient() {
    const $select = $('select[name="recipient_field"]');
    const $container = $(".recipient-dynamic-settings");

    // Hapus error sebelumnya
    $(".wanotify-field-error", $container).remove();
    $container.removeClass("has-error");

    if ($select.val() === "" || $select.val() === "--") {
      $select.after(
        '<div class="wanotify-field-error">Silakan pilih field</div>'
      );
      $container.addClass("has-error");
      return false;
    }

    return true;
  }

  /**
   * Inisialisasi validasi untuk pengaturan umum
   */
  function initGeneralSettingsValidation() {
    const $form = $("#wanotify-general-settings");

    if (!$form.length) return;

    // Validasi URL API
    $("#api_url").on("blur", function () {
      validateField($(this), WANotifyValidator.validateApiUrl);
    });

    // Validasi Token Autentikasi
    $("#access_token").on("blur", function () {
      validateField($(this), WANotifyValidator.validateAccessToken);
    });

    // Validasi Nomor WhatsApp Default
    $("#default_recipient").on("blur", function () {
      const result = validateField(
        $(this),
        WANotifyValidator.validateWhatsAppNumber
      );
      if (result.isValid && result.formatted !== $(this).val()) {
        $(this).val(result.formatted);
        showNotification(
          "info",
          "Nomor WhatsApp diformat ulang untuk standarisasi"
        );
      }
    });

    // Validasi Template Default
    $("#default_template").on("blur", function () {
      const result = validateField(
        $(this),
        WANotifyValidator.validateMessageTemplate
      );
      if (result.isValid && result.isWarning) {
        showFieldWarning($(this), result.message);
      }
    });

    // Override form submit untuk pengaturan umum
    $form.on("submit", function (e) {
      e.preventDefault();

      // Validasi semua field sebelum submit
      let isValid = true;

      // Validasi URL API
      const apiUrlResult = validateField(
        $("#api_url"),
        WANotifyValidator.validateApiUrl
      );
      if (!apiUrlResult.isValid) isValid = false;

      // Validasi Token Autentikasi
      const tokenResult = validateField(
        $("#access_token"),
        WANotifyValidator.validateAccessToken
      );
      if (!tokenResult.isValid) isValid = false;

      // Validasi Nomor WhatsApp Default
      const recipientResult = validateField(
        $("#default_recipient"),
        WANotifyValidator.validateWhatsAppNumber
      );
      if (!recipientResult.isValid) isValid = false;

      // Validasi Template Default
      const templateResult = validateField(
        $("#default_template"),
        WANotifyValidator.validateMessageTemplate
      );
      if (!templateResult.isValid) isValid = false;

      // Jika valid, lanjutkan submit
      if (isValid) {
        saveGeneralSettings($(this));
      } else {
        showNotification("error", "Harap perbaiki kesalahan pada formulir");
        // Fokus ke field error pertama
        $(".wanotify-form-input.has-error")
          .first()
          .find("input, textarea")
          .focus();
      }
    });
  }

  /**
   * Validasi field dengan fungsi validator tertentu
   * @param {jQuery} $field Element field yang akan divalidasi
   * @param {Function} validatorFn Fungsi validator
   * @returns {Object} Hasil validasi
   */
  function validateField($field, validatorFn) {
    // Reset state validasi
    removeFieldError($field);

    // Validasi dengan fungsi yang diberikan
    const result = validatorFn($field.val());

    if (!result.isValid) {
      showFieldError($field, result.message);
    }

    return result;
  }

  /**
   * Menampilkan error pada field
   * @param {jQuery} $field Field yang akan ditampilkan error
   * @param {string} message Pesan error
   */
  function showFieldError($field, message) {
    const $container = $field.closest(".wanotify-form-input");
    $container.addClass("has-error");
    $field.after('<div class="wanotify-field-error">' + message + "</div>");
  }

  /**
   * Menampilkan peringatan pada field
   * @param {jQuery} $field Field yang akan ditampilkan peringatan
   * @param {string} message Pesan peringatan
   */
  function showFieldWarning($field, message) {
    const $container = $field.closest(".wanotify-form-input");
    $container.addClass("has-warning");
    $field.after('<div class="wanotify-field-warning">' + message + "</div>");
  }

  /**
   * Menghapus error dari field
   * @param {jQuery} $field Field yang akan dihapus errornya
   */
  function removeFieldError($field) {
    const $container = $field.closest(".wanotify-form-input");
    $container.removeClass("has-error has-warning");
    $field.siblings(".wanotify-field-error, .wanotify-field-warning").remove();
  }

  /**
   * Inisialisasi sistem pengecekan konfigurasi
   */
  function initConfigChecker() {
    // Skip jika di halaman pengaturan umum
    if ($("#wanotify-general-settings").length) return;

    // Cek status konfigurasi melalui AJAX
    $.post(
      wanotify.ajax_url,
      {
        action: "wanotify_check_configuration",
        nonce: wanotify.nonce,
      },
      function (response) {
        if (!response.success || !response.data.is_complete) {
          // Tampilkan banner notifikasi
          showConfigurationNotice(response.data.validation_results);

          // Disable fitur yang memerlukan konfigurasi lengkap
          disableUnconfiguredFeatures();
        }
      }
    );
  }

  /**
   * Tampilkan banner notifikasi tentang konfigurasi yang belum lengkap
   * @param {Object} validationResults Hasil validasi dari server
   */
  function showConfigurationNotice(validationResults) {
    let message =
      "<strong>Perhatian:</strong> Beberapa pengaturan dasar belum dikonfigurasi dengan benar: ";
    let fieldMessages = [];

    for (const key in validationResults) {
      if (
        validationResults.hasOwnProperty(key) &&
        !validationResults[key].is_valid
      ) {
        fieldMessages.push(
          "<strong>" +
            validationResults[key].field_name +
            "</strong>: " +
            validationResults[key].message
        );
      }
    }

    message += fieldMessages.join(", ");
    message +=
      '<br><a href="' +
      wanotify.settings_url +
      '" class="button button-primary button-small">Lengkapi Konfigurasi Sekarang</a>';

    // Tampilkan banner yang persisten
    if ($(".wanotify-config-notice").length === 0) {
      $(".wanotify-header").after(
        '<div class="wanotify-config-notice">' + message + "</div>"
      );
    }
  }

  /**
   * Nonaktifkan fitur yang memerlukan konfigurasi lengkap
   */
  function disableUnconfiguredFeatures() {
    // Disable tombol tes notifikasi
    $("#wanotify-test-form-notification")
      .addClass("disabled")
      .prop("disabled", true)
      .attr("title", "Konfigurasi dasar belum lengkap");

    // Tambahkan info pada tombol
    $("#wanotify-test-form-notification").after(
      '<div class="wanotify-feature-blocked-info">Fitur ini memerlukan konfigurasi lengkap</div>'
    );

    // Disable toggle status formulir
    $(".wanotify-status-toggle").each(function () {
      const $this = $(this);
      if (!$this.hasClass("disabled")) {
        $this
          .addClass("disabled")
          .prop("disabled", true)
          .attr("title", "Konfigurasi dasar belum lengkap");
      }
    });
  }
})(jQuery);
