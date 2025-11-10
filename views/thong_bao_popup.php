<div id="popup" class="position-fixed bottom-0 end-0 m-3 p-3 bg-success text-white rounded shadow" style="display:none;">
  <strong>✅ Thành công!</strong> Sản phẩm đã được thêm vào giỏ hàng.
</div>

<script>
  function showPopup() {
    const p = document.getElementById("popup");
    p.style.display = "block";
    setTimeout(() => p.style.display = "none", 3000);
  }
</script>
