<?php $extra_js = $extra_js ?? []; ?>
  <script src="js/admin-layout.js"></script>
<?php foreach ($extra_js as $js): ?>
  <script src="<?php echo htmlspecialchars($js); ?>"></script>
<?php endforeach; ?>
</body>
</html>
