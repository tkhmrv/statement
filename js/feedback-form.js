document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("feedback-form");
  const toast = document.querySelector(".toasts-only");
  const toastIcon = toast.querySelector(".toast-icon");
  const toastText = toast.querySelector(".toasts-only-text-body");

  function showToast(message, type = "success") {
    toastIcon.src =
      type === "success" ? "/images/check.webp" : "/images/cross.webp";
    toastText.textContent = message;

    toast.style.display = "flex";
    toast.classList.remove("hiding");

    setTimeout(() => {
      toast.classList.add("hiding");
      setTimeout(() => {
        toast.classList.remove("hiding");
        toast.style.display = "none";
      }, 700);
    }, 4000);
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // showToast("Форма успешно отправлена!", "success");

    const formData = new FormData(form);

    try {
      const res = await fetch("/php/feedback-form.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        }
      });

      const data = await res.json();

      if (data.success) {
        form.reset();
        showToast("Форма успешно отправлена!", "success");
      } else {
        showToast(data.message || "Ошибка при отправке формы", "error");
      }
    } catch (err) {
      showToast("Ошибка соединения с сервером", "error");
      console.error(err);
    }
  });
});
