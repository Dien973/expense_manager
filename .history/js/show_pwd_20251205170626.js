document.querySelector(".toggle-eye").addEventListener("click", function () {
  const pwd = document.querySelector(".pwd");
  if (pwd.type === "password") {
    pwd.type = "text";
    this.classList.remove("fa-eye");
    this.classList.add("fa-eye-slash");
  } else {
    pwd.type = "password";
    this.classList.remove("fa-eye-slash");
    this.classList.add("fa-eye");
  }
});
