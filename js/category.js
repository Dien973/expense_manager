const iconList = [
  { class: "bx bx-universal-access" },
  { class: "bx bxs-baby-carriage" },
  { class: "bx bx-body" },
  { class: "bx bxs-dog" },
  { class: "bx bxs-right-top-arrow-circle" },
  { class: "bx bxs-right-down-arrow-circle" },
  { class: "bx bxl-meta" },
  { class: "bx bxl-tiktok" },
  { class: "bx bxl-instagram" },
  { class: "bx bxl-facebook" },
  { class: "bx bxl-youtube" },
  { class: "bx bxl-twitter" },
  { class: "bx bxs-wallet" },
  { class: "bx bxl-steam" },
  { class: "bx bx-money" },
  { class: "bx bxs-credit-card" },
  { class: "bx bxs-gift" },
  { class: "bx bxs-coin-stack" },
  { class: "bx bx-trending-up" },
  { class: "bx bx-dollar-circle" },
  { class: "bx bx-food-menu" },
  { class: "bx bx-car" },
  { class: "bx bx-home" },
  { class: "bx bx-game" },
  { class: "bx bx-shopping-bag" },
  { class: "bx bx-plus-medical" },
  { class: "bx bx-book" },
  { class: "bx bx-wifi" },
  { class: "bx bx-water" },
  { class: "bx bx-dish" },
  { class: "bx bx-coffee" },
  { class: "bx bx-movie" },
  { class: "bx bx-basketball" },
  { class: "bx bx-gas-pump" },
  { class: "bx bx-phone" },
  { class: "bx bx-heart" },
  { class: "bx bx-star" },
  { class: "bx bx-category" },
  { class: "bx bxs-playlist" },
];

document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        tab.classList.add('active');
        const target = tab.getAttribute('data-target');
        document.getElementById(target).classList.add('active');
    });
});

function openAddModal(type) {
    document.getElementById('modalTitle').textContent = `Thêm danh mục ${type}`;
    document.getElementById('categoryType').value = type;
    document.getElementById('categoryId').value = '';
    document.getElementById("categoryNote").value = "";
    
    <?php if ($user_role === 'admin'): ?>
    document.getElementById('isSystem').checked = false;
    <?php endif; ?>

    loadIcons('bx bx-category');
    document.getElementById('categoryModal').style.display = 'flex';
}
