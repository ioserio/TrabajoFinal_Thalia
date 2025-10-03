<?php
session_start();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Boutique — One-Frame Cover Scroll</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Luckiest+Guy&family=Shrikhand&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
</head>
<body class="intro-seq">
  <main>
    <?php include 'index.html'; ?>
  </main>
  <script>
    // Reemplazar todos los enlaces INGRESAR por CERRAR SESIÓN si hay sesión
    (function(){
      const logged = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
      if(!logged) return;
      document.querySelectorAll('a[href="login.html"]').forEach(a=>{
        a.href = 'logout.php';
        a.textContent = 'CERRAR SESIÓN';
      });
    })();
    // Asegurar que TIENDA vaya a productos.html
    (function(){
      document.querySelectorAll('a[href="#store"]').forEach(a=>{ a.href = 'productos.html'; });
    })();
    // Toast de suscripción y contacto
    (function(){
      const p = new URLSearchParams(location.search);
      const sub = p.get('sub');
      const contact = p.get('contact');
      let showed = false;
      if (sub){
        const t = document.createElement('div');
        t.className = 'toast' + (sub==='1' ? '' : ' toast-error');
        t.textContent = (sub==='1') ? '¡Suscripción exitosa!' : (sub==='dup' ? 'Ya estabas suscrito' : 'Correo inválido');
        document.body.appendChild(t);
        setTimeout(()=>{ t.remove(); }, 5000);
        showed = true;
      }
      if (contact){
        const t = document.createElement('div');
        t.className = 'toast' + (contact==='1' ? '' : ' toast-error');
        t.textContent = (contact==='1') ? '¡Mensaje enviado!' : 'No se pudo enviar tu mensaje';
        document.body.appendChild(t);
        setTimeout(()=>{ t.remove(); }, 5000);
        showed = true;
      }
      // Limpiar parámetros para que no reaparezcan en recarga
      if (showed) {
        p.delete('sub');
        p.delete('contact');
        const newQs = p.toString();
        const newUrl = location.pathname + (newQs ? ('?' + newQs) : '') + location.hash;
        history.replaceState(null, '', newUrl);
      }
    })();
  </script>
  <script src="script.js"></script>
</body>
</html>