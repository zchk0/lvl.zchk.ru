<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <title>PIN-код</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/pin-cooldown.js') }}" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body class="min-h-screen flex items-center justify-center bg-slate-900">
    <div
      class="w-full max-w-sm mx-4 bg-slate-800 rounded-3xl shadow-2xl px-8 py-10 flex flex-col items-center gap-6"
    >
      <div class="flex flex-col items-center gap-2 text-center">
        <h1 class="text-xl sm:text-2xl font-semibold text-white">
          Введите PIN-код
        </h1>
        <p class="text-slate-400 text-sm sm:text-base">
          Нужен код для доступа сюды :&lt;
        </p>
      </div>

      <form
        class="w-full flex flex-col gap-6"
        id="pin-form"
        method="POST"
        action="{{ route('pin.check') }}"
      >
        @csrf

        {{-- Сюда JS запишет полный PIN --}}
        <input type="hidden" name="pin" id="pin-full">

        <div class="flex justify-between gap-3">
          @for($i = 0; $i < 4; $i++)
            <input
              type="password"
              maxlength="1"
              inputmode="numeric"
              pattern="[0-9]*"
              class="w-14 h-14 sm:w-16 sm:h-16 text-center text-2xl font-semibold 
                     rounded-2xl border border-slate-600 bg-slate-900/60 
                     text-white outline-none focus:border-indigo-500 
                     focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0
                     transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              @disabled($retryAfter > 0)
              data-pin-input
            />
          @endfor
        </div>

        @if($retryAfter > 0)
          @include('partials.pin-cooldown')
        @else
          @error('pin')
            <div class="text-center text-xs sm:text-sm text-red-400">
              {{ $message }}
            </div>
          @enderror
        @endif

        <button
          type="submit"
          class="mt-2 w-full py-3 rounded-2xl text-sm sm:text-base font-semibold 
                 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-700
                 text-white shadow-lg shadow-indigo-500/30
                 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          id="pin-submit"
          disabled
        >
          Подтвердить
        </button>

        <a
          href="{{ route('admin.login') }}"
          class="text-center text-xs text-slate-500 hover:text-slate-300 transition"
        >
          Администратор
        </a>

        {{-- 
        <div class="text-center text-xs sm:text-sm text-slate-400">
          Не получили код?
          <button
            type="button"
            class="text-indigo-400 hover:text-indigo-300 font-medium underline-offset-2 hover:underline"
          >
            Отправить ещё раз
          </button>
        </div> 
        --}}
      </form>
    </div>

    <script>
      const inputs = document.querySelectorAll("[data-pin-input]");
      const submitButton = document.getElementById("pin-submit");
      const form = document.getElementById("pin-form");
      const pinFullInput = document.getElementById("pin-full");

      function updateSubmitState() {
        const allFilled = Array.from(inputs).every((input) => input.value.trim() !== "");
        submitButton.disabled = !allFilled || !!document.querySelector('[data-pin-cooldown]');
      }

      inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
          const value = e.target.value.replace(/\D/g, ""); // только цифры
          e.target.value = value.slice(0, 1); // максимум 1 символ

          if (value && index < inputs.length - 1) {
            inputs[index + 1].focus();
          }

          updateSubmitState();
        });

        input.addEventListener("keydown", (e) => {
          if (e.key === "Backspace" && !e.target.value && index > 0) {
            inputs[index - 1].focus();
          }
        });

        input.addEventListener("paste", (e) => {
          e.preventDefault();
          const paste = (e.clipboardData || window.clipboardData)
            .getData("text")
            .replace(/\D/g, "")
            .slice(0, inputs.length);

          paste.split("").forEach((char, i) => {
            if (inputs[index + i]) {
              inputs[index + i].value = char;
            }
          });

          if (inputs[Math.min(index + paste.length, inputs.length - 1)]) {
            inputs[Math.min(index + paste.length, inputs.length - 1)].focus();
          }

          updateSubmitState();
        });
      });

      form.addEventListener("submit", (e) => {
        if (document.querySelector('[data-pin-cooldown]')) {
          e.preventDefault();
          return;
        }

        // НЕ отменяем отправку, просто подготавливаем поле pin
        const pin = Array.from(inputs)
          .map((input) => input.value)
          .join("");

        pinFullInput.value = pin;
      });
    </script>
  </body>
</html>
