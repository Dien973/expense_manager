const eyes = document.querySelectorAll(".toggle-eye");
const pwds = document.querySelectorAll(".pwd");

eyes.forEach((eye, index) => {
  eye.onclick = () => {
    if (pwds[index].type === "password") {
      pwds[index].type = "text";
      eye.classList.remove("fa-eye");
      eye.classList.add("fa-eye-slash");
    } else {
      pwds[index].type = "password";
      eye.classList.remove("fa-eye-slash");
      eye.classList.add("fa-eye");
    }
  };
});
