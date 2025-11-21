<?php
session_start();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Boutique — Productos</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Luckiest+Guy&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
  <style>
    /* Ajustes específicos de la página de productos (ligeros y no intrusivos) */
    .shop-toolbar{position:sticky; top:56px; z-index:48; background:rgba(255,255,255,.82); backdrop-filter:blur(10px); box-shadow:0 6px 22px -10px rgba(0,0,0,.18); border:1px solid #eee; border-left:none; border-right:none}
    .shop-toolbar-inner{width:100%; margin:0; padding:.7rem 1rem; display:grid; grid-template-columns:1fr auto auto; gap:.6rem; align-items:center}
    .filter-chips{display:flex; gap:.4rem; flex-wrap:wrap}
    .chip{border:1px solid #e7e7e7; padding:.4rem .7rem; border-radius:999px; font-size:.8rem; background:#fff; cursor:pointer; transition:.2s}
    .chip.active, .chip:hover{background:#111; color:#fff; border-color:#111}
    .shop-search{display:flex; align-items:center; gap:.45rem; background:rgba(255,255,255,.9); padding:.35rem .55rem; border-radius:999px; border:1px solid #e7e7e7}
    .shop-search input{border:none; outline:none; background:transparent; min-width:160px}
    .shop-sort{border:1px solid #e7e7e7; border-radius:10px; padding:.4rem .5rem; background:#fff}
    .topbar .right-actions{margin-left:auto; display:flex; gap:.5rem; align-items:center}
    .topbar .right-actions a{color:#222; text-decoration:none; font-size:.82rem; padding:.4rem .7rem; border-radius:999px; border:1px solid #e7e7e7; background:#fff}
    .user-greet{font-size:.85rem; color:#333; padding:.4rem .6rem; border:1px solid #e7e7e7; border-radius:999px; background:#fff}
  /* Botón carrito */
  .cart-btn{position:relative; display:inline-flex; align-items:center; gap:.45rem; padding:.4rem .7rem; border-radius:999px; border:1px solid #e7e7e7; background:#fff; cursor:pointer}
  .cart-btn .icon{font-size:1rem}
  .cart-badge{position:absolute; top:-6px; right:-6px; background:#111; color:#fff; border-radius:999px; font-size:.68rem; padding:.05rem .4rem; line-height:1; border:2px solid #fff; min-width:18px; text-align:center}
    /* Migas de pan en encabezado para evitar duplicar categorías */
    .crumbs{display:flex; gap:.4rem; align-items:center; margin-left:1rem; color:#666; font-size:.9rem}
    .crumbs a{color:#444; text-decoration:none}
    .crumbs strong{color:#222}
    /* Forzar contenedores a ocupar todo el ancho en esta página */
    .catalog .frame-card{width:100%; max-width:none;}
    @media (max-width:900px){
      .shop-toolbar-inner{grid-template-columns:1fr; gap:.55rem}
      .shop-search input{width:100%}
    }
  </style>
  <script>
    // Ajustar acciones de sesión en la barra superior si vienes con sesión iniciada (igual que index.php)
    document.addEventListener('DOMContentLoaded', function(){
      const params = new URLSearchParams(location.search);
      const qsUser = params.get('u'); // opcional
    });
  </script>
  </head>
<body>
  <header class="topbar">
    <div class="brand">PERSONAL</div>
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="index.php">Inicio</a>
      <span>/</span>
      <strong>Catálogo</strong>
    </nav>
    <div class="right-actions">
      <?php if(isset($_SESSION['user_id'])): ?>
        <span class="user-greet">Hola, <?php echo htmlspecialchars($_SESSION['nombres'] ?? 'Usuario'); ?></span>
        <a href="logout.php">Cerrar sesión</a>
      <?php else: ?>
        <a href="login.html">Ingresar</a>
      <?php endif; ?>
      <a href="index.php#contact" class="contact-link">Contacto</a>
      <button type="button" class="cart-btn" id="openCartBtn" aria-label="Carrito">
        <span class="icon">🛒</span>
        <span>Carrito</span>
        <span class="cart-badge" id="cartCount" aria-label="Artículos en carrito">0</span>
      </button>
    </div>
  </header>

  <!-- Toolbar de filtros / búsqueda / orden -->
  <div class="shop-toolbar">
    <div class="shop-toolbar-inner">
      <div class="filter-chips" aria-label="Filtros">
        <button class="chip active" type="button" onclick="location.hash='#ropa'">Ropa</button>
        <button class="chip" type="button" onclick="location.hash='#calzado'">Calzado</button>
        <button class="chip" type="button" onclick="location.hash='#accesorios'">Accesorios</button>
        <button class="chip" type="button" onclick="location.hash='#novedades'">Novedades</button>
      </div>
      <label class="shop-search" aria-label="Buscar productos">
        🔎 <input type="search" placeholder="Buscar prendas, colores, estilos..." oninput="window.__filter && window.__filter(this.value)" />
      </label>
      <label>
        <select class="shop-sort" aria-label="Ordenar" onchange="window.__sort && window.__sort(this.value)">
          <option value="pop">Populares</option>
          <option value="asc">Precio: Bajo a Alto</option>
          <option value="desc">Precio: Alto a Bajo</option>
          <option value="new">Novedades</option>
        </select>
      </label>
    </div>
  </div>

  <main class="catalog">
    <section class="intro frame-card in-view">
      <div class="catalog-hero">
        <h1 class="kicker reveal-first">Colección Actual</h1>
        <p class="reveal-mid">Explora nuestras categorías y encuentra piezas clave para tu día a día.</p>
        <div class="catalog-tags reveal-late">
          <span>#minimal</span><span>#urbano</span><span>#atemporal</span>
        </div>
      </div>
    </section>

    <!-- CATEGORÍA: ROPA -->
    <section id="ropa" class="product-category">
      <div class="frame-card">
        <header class="category-head">
          <h2 class="reveal-first">Ropa</h2>
          <p class="reveal-mid">Prendas versátiles para combinar y repetir sin perder estilo.</p>
        </header>
        <div class="product-grid" data-cat="ropa">
          <article class="p-card reveal-first" data-name="Camisa Lino Beige" data-price="39.90">
            <img src="https://images.unsplash.com/photo-1520970014086-2208d157c9e2?q=80&w=800&auto=format&fit=crop" alt="Camisa lino beige" />
            <h3>Camisa Lino Beige</h3>
            <p class="price">S/39.90</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-first" data-name="Blazer Negro" data-price="79.00">
            <img src="https://images.unsplash.com/photo-1503341455253-b2e723bb3dbb?q=80&w=800&auto=format&fit=crop" alt="Blazer negro" />
            <h3>Blazer Negro</h3>
            <p class="price">S/79.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-mid" data-name="Pantalón Recto Arena" data-price="49.50">
            <img src="https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=800&auto=format&fit=crop" alt="Pantalón recto arena" />
            <h3>Pantalón Recto Arena</h3>
            <p class="price">S/49.50</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Sudadera Oversized" data-price="59.00">
            <img src="https://www.desire.pe/cdn/shop/files/183.png?v=1698265393&width=600" alt="Sudadera oversized crema" />
            <h3>Sudadera Oversized</h3>
            <p class="price">S/59.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Vestido Midi Rosa" data-price="69.00">
            <img src="https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?q=80&w=800&auto=format&fit=crop" alt="Vestido midi rosa" />
            <h3>Vestido Midi Rosa</h3>
            <p class="price">S/69.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Chaqueta Denim Azul" data-price="74.00">
            <img src="https://m.media-amazon.com/images/I/51uJbq-5FjL._AC_.jpg" alt="Chaqueta denim azul" />
            <h3>Chaqueta Denim Azul</h3>
            <p class="price">S/74.00</p>
            <button>Añadir</button>
          </article>
        </div>
      </div>
    </section>

    <!-- CATEGORÍA: CALZADO -->
    <section id="calzado" class="product-category">
      <div class="frame-card">
        <header class="category-head">
          <h2 class="reveal-first">Calzado</h2>
          <p class="reveal-mid">Comodidad y carácter en cada paso.</p>
        </header>
        <div class="product-grid" data-cat="calzado">
          <article class="p-card reveal-first" data-name="Zapatillas Minimal" data-price="72.00">
            <img src="https://images.unsplash.com/photo-1600185365483-26d7a4cc7519?q=80&w=800&auto=format&fit=crop" alt="Zapatillas blancas" />
            <h3>Zapatillas Minimal</h3>
            <p class="price">S/72.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-mid" data-name="Botín Cuero" data-price="110.00">
            <img src="imagenes/botin cuero.jpg" alt="Botín cuero" />
            <h3>Botín Cuero</h3>
            <p class="price">S/110.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-mid" data-name="Mocasín Marrón" data-price="89.90">
            <img src="https://images.unsplash.com/photo-1603808033192-082d6919d3e1?q=80&w=800&auto=format&fit=crop" alt="Mocasín marrón" />
            <h3>Mocasín Marrón</h3>
            <p class="price">S/89.90</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Sandalia Piel" data-price="64.00">
            <img src="https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?q=80&w=800&auto=format&fit=crop" alt="Sandalia piel" />
            <h3>Sandalia Piel</h3>
            <p class="price">S/64.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Bota Chelsea Negra" data-price="120.00">
            <img src="https://shoppinghavana.com/wp-content/uploads/2025/05/file_00000000c53861f899d48490a88dc305.jpg" alt="Bota chelsea negra" />
            <h3>Bota Chelsea Negra</h3>
            <p class="price">S/120.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Zapatilla Running Pro" data-price="98.00">
            <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=800&auto=format&fit=crop" alt="Zapatilla running profesional" />
            <h3>Zapatilla Running Pro</h3>
            <p class="price">S/98.00</p>
            <button>Añadir</button>
          </article>
        </div>
      </div>
    </section>

    <!-- CATEGORÍA: ACCESORIOS -->
    <section id="accesorios" class="product-category">
      <div class="frame-card">
        <header class="category-head">
          <h2 class="reveal-first">Accesorios</h2>
          <p class="reveal-mid">Detalles que cierran tu look y lo hacen único.</p>
        </header>
        <div class="product-grid" data-cat="accesorios">
          <article class="p-card reveal-first" data-name="Bolso Tote Lona" data-price="45.00">
            <img src="https://plazavea.vteximg.com.br/arquivos/ids/31325290-418-418/imageUrl_1.jpg" alt="Bolso tote" />
            <h3>Bolso Tote Lona</h3>
            <p class="price">S/45.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-first" data-name="Gorra Algodón" data-price="22.00">
            <img src="https://m.media-amazon.com/images/I/51r2lXHeJOL._AC_SY1000_.jpg" alt="Gorra beige" />
            <h3>Gorra Algodón</h3>
            <p class="price">S/22.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-mid" data-name="Gafas Pasta" data-price="34.90">
            <img src="https://soloptical.net/pub/media/catalog/product/cache/a237138a07ed0dd2cc8a6fa440635ea6/2/4/24-497-17-01_5516_145.jpg" alt="Gafas pasta" />
            <h3>Gafas Pasta</h3>
            <p class="price">S/34.90</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Reloj Minimal" data-price="120.00">
            <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=800&auto=format&fit=crop" alt="Reloj minimal" />
            <h3>Reloj Minimal</h3>
            <p class="price">S/120.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Cinturón de Cuero" data-price="28.00">
            <img src="https://blackbubba.com.pe/cdn/shop/files/belt-browne-105-1-c86eb425-9603-4943-9d3d-658b16072c8a_8632b693-f127-483c-ae1f-8d06b9e33c0e.jpg?v=1708108483&width=416" alt="Cinturón de cuero marrón" />
            <h3>Cinturón de Cuero</h3>
            <p class="price">S/28.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Bufanda de Lana" data-price="26.00">
            <img src="https://i.pinimg.com/474x/00/84/8a/00848a8b948a84dfec0b275919b982c1.jpg" alt="Bufanda de lana" />
            <h3>Bufanda de Lana</h3>
            <p class="price">S/26.00</p>
            <button>Añadir</button>
          </article>
        </div>
      </div>
    </section>

    <!-- CATEGORÍA: NOVEDADES -->
    <section id="novedades" class="product-category">
      <div class="frame-card">
        <header class="category-head">
          <h2 class="reveal-first">Novedades</h2>
          <p class="reveal-mid">Ediciones recientes y piezas de lanzamiento limitado.</p>
        </header>
        <div class="product-grid" data-cat="novedades">
          <article class="p-card reveal-first" data-name="Chaqueta Técnica" data-price="130.00" data-new="1">
            <img src="https://topmotorbike.com/wp-content/uploads/2021/06/98185-21VW_F.jpg" alt="Chaqueta técnica" />
            <h3>Chaqueta Técnica</h3>
            <p class="price">S/130.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-mid" data-name="Sneaker Limitada" data-price="150.00" data-new="1">
            <img src="https://images.unsplash.com/photo-1544986581-efac024faf62?q=80&w=800&auto=format&fit=crop" alt="Sneaker edición limitada" />
            <h3>Sneaker Limitada</h3>
            <p class="price">S/150.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-mid" data-name="Bolso Cilindro" data-price="68.00" data-new="1">
            <img src="https://x3madrid.com/wp-content/uploads/bolso-cilindro-camel.jpg" alt="Bolso cilindro" />
            <h3>Bolso Cilindro</h3>
            <p class="price">S/68.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Camiseta Gráfica" data-price="29.90" data-new="1">
            <img src="https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?q=80&w=800&auto=format&fit=crop" alt="Camiseta gráfica" />
            <h3>Camiseta Gráfica</h3>
            <p class="price">S/29.90</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Parka Ligera" data-price="140.00" data-new="1">
            <img src="https://image.made-in-china.com/202f0j00aSkbpJmHfcqf/Women-s-Outdoor-Waterproof-Breathable-Light-Weight-Puff-Parka-Plume-Grey.webp" alt="Parka ligera" />
            <h3>Parka Ligera</h3>
            <p class="price">S/140.00</p>
            <button>Añadir</button>
          </article>
          <article class="p-card reveal-late" data-name="Gorro Beanie Orgánico" data-price="19.90" data-new="1">
            <img src="https://images.unsplash.com/photo-1516826957135-700dedea698c?q=80&w=800&auto=format&fit=crop" alt="Gorro beanie orgánico" />
            <h3>Gorro Beanie Orgánico</h3>
            <p class="price">S/19.90</p>
            <button>Añadir</button>
          </article>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <p>&copy; 2025 Boutique Personal. Todos los derechos reservados.</p>
  </footer>

  <!-- Modal Carrito -->
  <div id="cartModal" class="modal-backdrop" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-card" style="max-width:min(680px, 92vw);">
      <button type="button" class="modal-close" aria-label="Cerrar">×</button>
      <h3>Tu Carrito</h3>
      <div id="cartList" style="display:grid; gap:.6rem; margin:.4rem 0 .6rem;"></div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:.6rem;">
        <strong>Total: <span id="cartTotal">S/0.00</span></strong>
        <div style="display:flex; gap:.5rem;">
          <button type="button" id="clearCartBtn" style="background:transparent;color:var(--rose-2);border:1.5px solid var(--rose-2); border-radius:12px; padding:.6rem .9rem; font-weight:700;">Vaciar</button>
          <button type="button" id="checkoutBtn" class="main-btn" style="width:auto; padding:.6rem 1rem;">Finalizar compra</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Búsqueda y ordenamiento básicos en el cliente (sin backend)
    (function(){
      const allGrids = Array.from(document.querySelectorAll('.product-grid'));
      const cards = Array.from(document.querySelectorAll('.product-grid .p-card'));
      function filter(query=''){
        const q = query.trim().toLowerCase();
        cards.forEach(c=>{
          const name = (c.dataset.name||'').toLowerCase();
          c.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
      }
      function sort(mode='pop'){
        allGrids.forEach(grid=>{
          const items = Array.from(grid.children);
          items.sort((a,b)=>{
            const pa = parseFloat(a.dataset.price||'0');
            const pb = parseFloat(b.dataset.price||'0');
            if(mode==='asc') return pa - pb;
            if(mode==='desc') return pb - pa;
            if(mode==='new') return (parseInt(b.dataset.new||'0') - parseInt(a.dataset.new||'0')) || (pb - pa);
            return 0; // populares (sin cambio determinístico)
          });
          items.forEach(it=>grid.appendChild(it));
        });
      }
      window.__filter = filter; window.__sort = sort;
    })();
  </script>
  <script>
    // Carrito con fallback: intenta API PHP (sesión) y cae a localStorage si 401
    (function(){
      const LS_KEY = 'boutique_cart_v1';
      const btns = Array.from(document.querySelectorAll('.product-grid .p-card button'));
      const countEl = document.getElementById('cartCount');
      const openBtn = document.getElementById('openCartBtn');
      const cartModal = document.getElementById('cartModal');
      const closeBtn = cartModal?.querySelector('.modal-close');
      const listEl = document.getElementById('cartList');
      const totalEl = document.getElementById('cartTotal');
      const clearBtn = document.getElementById('clearCartBtn');
      const checkoutBtn = document.getElementById('checkoutBtn');

      async function api(method='GET', action='list', body){
        try{
          const res = await fetch(`cart_api.php?action=${encodeURIComponent(action)}`, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
            credentials: 'same-origin'
          });
          if(res.status === 401) throw new Error('unauthorized');
          if(!res.ok) throw new Error('api_error');
          return await res.json();
        }catch(e){
          if(e && e.message === 'unauthorized') return { error: 'unauthorized' };
          return null;
        }
      }

      function load(){
        try{ return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); }catch{ return []; }
      }
      function save(items){ localStorage.setItem(LS_KEY, JSON.stringify(items)); }
      function updateBadge(items){ if(countEl) countEl.textContent = items.reduce((s,it)=>s+it.qty,0); }
      async function addItem(item){
        // Try API first (requiere sesión para persistir en BD)
        const server = await api('POST','add',{ name:item.name, price:parseFloat(item.price||0), img:item.img });
        if(server && server.error === 'unauthorized'){
          alert('Inicia sesión para guardar tu carrito en tu cuenta.');
          try{ location.href = 'login.html'; }catch{}
          return;
        }
        if(server){ updateBadge(server.items || []); return renderServer(server); }
        // Fallback local
        const items = load();
        const idx = items.findIndex(x=>x.name===item.name);
        if(idx>=0){ items[idx].qty += 1; } else { items.push({...item, qty:1}); }
        save(items); updateBadge(items); renderLocal(items);
      }
      async function removeById(id){
        const server = await api('POST','remove',{ id_producto:id });
        if(server){ updateBadge(server.items || []); return renderServer(server); }
      }
      async function setQtyById(id, qty){
        const server = await api('POST','update',{ id_producto:id, qty });
        if(server){ updateBadge(server.items || []); return renderServer(server); }
      }
      function removeLocal(name){
        let items = load(); items = items.filter(x=>x.name!==name);
        save(items); updateBadge(items); renderLocal(items);
      }
      function changeQtyLocal(name, delta){
        const items = load(); const it = items.find(x=>x.name===name); if(!it) return;
        it.qty = Math.max(1, it.qty + delta);
        save(items); updateBadge(items); renderLocal(items);
      }

      function renderLocal(items = load()){
        if(!listEl) return;
        if(items.length===0){
          listEl.innerHTML = '<div style="color:#555">Tu carrito está vacío.</div>';
          totalEl.textContent = 'S/0.00';
          return;
        }
        listEl.innerHTML = '';
        let total = 0;
        items.forEach(it=>{
          const price = parseFloat(it.price||0);
          total += price * it.qty;
          const row = document.createElement('div');
          row.style.display='grid';
          row.style.gridTemplateColumns='64px 1fr auto';
          row.style.gap='.6rem';
          row.style.alignItems='center';
          row.style.border='1px solid #eee';
          row.style.borderRadius='12px';
          row.style.padding='.45rem';
          row.innerHTML = `
            <img src="${it.img}" alt="${it.name}" style="width:64px;height:64px;object-fit:cover;border-radius:10px" onerror="this.onerror=null;this.src='https://placehold.co/64x64?text=No+img';" />
            <div>
              <div style="font-weight:700;margin-bottom:.2rem">${it.name}</div>
              <div style="font-size:.9rem;color:#555">S/${price.toFixed(2)}</div>
            </div>
            <div style="display:flex; gap:.4rem; align-items:center;">
              <button type="button" aria-label="Disminuir" data-act="dec_local" data-name="${it.name}" style="width:28px;height:28px;border-radius:8px;border:1px solid #ddd;background:#fff">−</button>
              <span aria-label="Cantidad" style="min-width:20px; text-align:center; font-weight:700">${it.qty}</span>
              <button type="button" aria-label="Aumentar" data-act="inc_local" data-name="${it.name}" style="width:28px;height:28px;border-radius:8px;border:1px solid #ddd;background:#fff">+</button>
              <button type="button" aria-label="Eliminar" data-act="del_local" data-name="${it.name}" style="margin-left:.4rem; border:1px solid #e74c3c; color:#e74c3c; background:#fff; border-radius:8px; padding:.3rem .5rem;">Quitar</button>
            </div>`;
          listEl.appendChild(row);
        });
        totalEl.textContent = 'S/' + total.toFixed(2);
        listEl.querySelectorAll('button[data-act$="_local"]').forEach(b=>{
          b.addEventListener('click', ()=>{
            const act = b.dataset.act; const name = b.dataset.name;
            if(act==='inc_local') changeQtyLocal(name, +1);
            else if(act==='dec_local') changeQtyLocal(name, -1);
            else if(act==='del_local') removeLocal(name);
          });
        });
      }
      function renderServer(payload){
        const items = (payload && payload.items) ? payload.items : [];
        if(!listEl) return;
        if(items.length===0){
          listEl.innerHTML = '<div style="color:#555">Tu carrito está vacío.</div>';
          totalEl.textContent = 'S/0.00';
          return;
        }
        listEl.innerHTML = '';
        let total = 0;
        items.forEach(it=>{
          const price = parseFloat(it.price||0); total += price * it.qty;
          const row = document.createElement('div');
          row.style.display='grid'; row.style.gridTemplateColumns='64px 1fr auto'; row.style.gap='.6rem'; row.style.alignItems='center'; row.style.border='1px solid #eee'; row.style.borderRadius='12px'; row.style.padding='.45rem';
          const img = it.img || '';
          row.innerHTML = `
            <img src="${img}" alt="${it.name}" style="width:64px;height:64px;object-fit:cover;border-radius:10px" onerror="this.onerror=null;this.src='https://placehold.co/64x64?text=No+img';" />
            <div>
              <div style=\"font-weight:700;margin-bottom:.2rem\">${it.name}</div>
              <div style=\"font-size:.9rem;color:#555\">S/${price.toFixed(2)}</div>
            </div>
            <div style=\"display:flex; gap:.4rem; align-items:center;\">
              <button type=\"button\" aria-label=\"Disminuir\" data-act=\"dec_server\" data-id=\"${it.id_producto}\" style=\"width:28px;height:28px;border-radius:8px;border:1px solid #ddd;background:#fff\">−</button>
              <span aria-label=\"Cantidad\" style=\"min-width:20px; text-align:center; font-weight:700\">${it.qty}</span>
              <button type=\"button\" aria-label=\"Aumentar\" data-act=\"inc_server\" data-id=\"${it.id_producto}\" style=\"width:28px;height:28px;border-radius:8px;border:1px solid #ddd;background:#fff\">+</button>
              <button type=\"button\" aria-label=\"Eliminar\" data-act=\"del_server\" data-id=\"${it.id_producto}\" style=\"margin-left:.4rem; border:1px solid #e74c3c; color:#e74c3c; background:#fff; border-radius:8px; padding:.3rem .5rem;\">Quitar</button>
            </div>`;
          listEl.appendChild(row);
        });
        totalEl.textContent = 'S/' + total.toFixed(2);
        listEl.querySelectorAll('button[data-act$="_server"]').forEach(b=>{
          b.addEventListener('click', async ()=>{
            const act = b.dataset.act; const id = parseInt(b.dataset.id||'0',10);
            if(!id) return;
            if(act==='inc_server') return setQtyById(id, (parseInt(b.previousElementSibling?.textContent||'1',10) + 1) || 1);
            if(act==='dec_server') return setQtyById(id, Math.max(1, (parseInt(b.nextElementSibling?.textContent||'1',10) - 1) || 1));
            if(act==='del_server') return removeById(id);
          });
        });
      }
      async function open(){
        cartModal?.classList.add('open');
        const server = await api('GET','list');
        if(server && server.error === 'unauthorized'){
          alert('Inicia sesión para ver y guardar tu carrito en la base de datos.');
          try{ location.href = 'login.html'; }catch{}
          return;
        }
        if(server){ updateBadge(server.items || []); return renderServer(server); }
        renderLocal();
      }
      function close(){ cartModal?.classList.remove('open'); }

      // Bind add buttons
      btns.forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const card = btn.closest('.p-card');
          if(!card) return;
          const name = card.querySelector('h3')?.textContent?.trim() || 'Producto';
          const img = card.querySelector('img')?.src || '';
          const price = card.dataset.price || '0';
          addItem({name, img, price});
        });
      });
      // Open/close
      openBtn?.addEventListener('click', open);
      closeBtn?.addEventListener('click', close);
      cartModal?.addEventListener('click', (e)=>{ if(e.target===cartModal) close(); });
      window.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });
      clearBtn?.addEventListener('click', async ()=>{
        const server = await api('POST','clear',{});
        if(server && server.error === 'unauthorized'){
          alert('Debes iniciar sesión para vaciar el carrito del servidor.');
          try{ location.href = 'login.html'; }catch{}
          return;
        }
        if(server){ updateBadge(server.items || []); return renderServer(server); }
        save([]); updateBadge([]); renderLocal([]);
      });
      checkoutBtn?.addEventListener('click', ()=>{
        alert('Gracias por tu compra (demo). Integraremos el checkout real aquí.');
      });
      // Init badge using server when possible
      (async ()=>{
        const server = await api('GET','list');
        if(server && !server.error){ updateBadge(server.items || []); }
        else { updateBadge(load()); }
      })();
    })();
  </script>
  <script>
    // Breadcrumb dinámico y chips activos según sección visible/hash
    (function(){
      const sections = Array.from(document.querySelectorAll('.product-category'));
      const chips = Array.from(document.querySelectorAll('.filter-chips .chip'));
      const crumbNav = document.querySelector('.crumbs');
      function setActiveChip(id){
        chips.forEach(ch=>{
          const txt = ch.textContent.trim().toLowerCase();
          ch.classList.toggle('active', ('#'+txt) === ('#'+id));
        });
      }
      function setCrumbLabel(id){
        if(!crumbNav) return;
        const strong = crumbNav.querySelector('strong');
        const pretty = id.charAt(0).toUpperCase() + id.slice(1);
        if(strong) strong.textContent = 'Catálogo / ' + pretty;
      }
      function onHash(){
        const id = (location.hash.replace('#','') || 'ropa');
        setActiveChip(id);
        setCrumbLabel(id);
      }
      window.addEventListener('hashchange', onHash);
      onHash();
      // Observer para actualizar cuando se hace scroll manual sin hash
      try{
        const io = new IntersectionObserver((entries)=>{
          const visible = entries
            .filter(e=>e.isIntersecting)
            .sort((a,b)=> b.intersectionRatio - a.intersectionRatio)[0];
          if(!visible) return;
          const id = visible.target.id;
          if(!id) return;
          setActiveChip(id);
          setCrumbLabel(id);
        }, {root:null, threshold:[0.15,0.35,0.55]});
        sections.forEach(s=>io.observe(s));
      }catch{}
    })();
  </script>
  <script>
    // Fallback visual para imágenes rotas en el catálogo
    // Si alguna URL externa cae (ej: Unsplash cambia permisos), mostramos un placeholder estable
    document.addEventListener('DOMContentLoaded', function(){
      const placeholder = 'https://placehold.co/800x600?text=Imagen+no+disponible';
      document.querySelectorAll('.product-grid img').forEach(img => {
        img.addEventListener('error', function onErr(){
          if(img.dataset.fallbackApplied) return;
          img.dataset.fallbackApplied = '1';
          img.src = placeholder;
          img.style.background = '#f5f5f5';
          img.removeEventListener('error', onErr);
        });
      });
    });
  </script>
  <script src="script.js"></script>
</body>
</html>
