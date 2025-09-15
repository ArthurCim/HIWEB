(function () {
      // menangani semua tombol .pw-toggle
      const toggles = document.querySelectorAll('.pw-toggle');

      toggles.forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const eye = btn.querySelector('.icon-eye');
        const eyeOff = btn.querySelector('.icon-eye-off');

        function updateButton(isShown) {
          btn.setAttribute('aria-pressed', isShown ? 'true' : 'false');
          btn.setAttribute('aria-label', isShown ? 'Sembunyikan password' : 'Tampilkan password');
          btn.title = isShown ? 'Sembunyikan password' : 'Tampilkan password';
          if (eye) eye.style.display = isShown ? 'none' : 'block';
          if (eyeOff) eyeOff.style.display = isShown ? 'block' : 'none';
        }

        btn.addEventListener('click', () => {
          // jika input ditemukan
          if (!input) return;
          const willShow = input.type === 'password';
          input.type = willShow ? 'text' : 'password';
          updateButton(willShow);
        });

        // keyboard support (Enter / Space)
        btn.addEventListener('keydown', (e) => {
          if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            btn.click();
          }
        });

        // inisialisasi state
        updateButton(false);
      });
    })();