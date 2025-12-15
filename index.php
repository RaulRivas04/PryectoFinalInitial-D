<?php include("php/header.php"); ?>

<main>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-content">
            <h1>Bienvenido a <span>Initial D</span></h1>
            <p>La plataforma líder en vehículos, repuestos y accesorios oficiales.</p>
            <a href="php/productos.php" class="btn btn-primary">Explorar catálogo</a>

            <!-- Badges hero -->
            <div class="hero-badges">
                <div class="badge"><i class="fa-solid fa-circle-check"></i> Calidad garantizada</div>
                <div class="badge"><i class="fa-solid fa-car"></i> Repuestos certificados</div>
                <div class="badge"><i class="fa-solid fa-bolt"></i> Envíos rápidos</div>
            </div>
        </div>
    </section>

    <!-- BENEFICIOS -->
    <section class="beneficios">
        <div class="beneficio-item">
            <i class="fa-solid fa-medal"></i>
            <h3>Productos Premium</h3>
            <p>Seleccionados para asegurar el mejor rendimiento.</p>
        </div>

        <div class="beneficio-item">
            <i class="fa-solid fa-shield-halved"></i>
            <h3>Compra segura</h3>
            <p>Protección y garantía en todas tus compras.</p>
        </div>

        <div class="beneficio-item">
            <i class="fa-solid fa-truck-fast"></i>
            <h3>Envíos urgentes</h3>
            <p>Entrega rápida 24/48h en la mayoría de productos.</p>
        </div>
    </section>

    <!-- CARRUSEL PRODUCTOS -->
    <section class="carousel-section">
    <h2>Productos destacados</h2>

    <div class="carousel" role="region" aria-label="Carrusel de productos destacados">
        <div class="carousel-track">

            <!-- Nissan GT-R R34 -->
            <div class="carousel-item active">
                <img src="img/destacada1.jpg" alt="Nissan Skyline GT-R R34">
                <div class="carousel-caption">
                    <h3>Nissan GT-R R34</h3>
                    <p>Icono japonés con motor RB26DETT y tracción total legendaria.</p>
                </div>
            </div>

            <!-- Yamaha R1 -->
            <div class="carousel-item">
                <img src="img/destacada2.jpg" alt="Yamaha R1">
                <div class="carousel-caption">
                    <h3>Yamaha R1</h3>
                    <p>Motocicleta de 1000cc con tecnología de competición.</p>
                </div>
            </div>

            <!-- Camiseta Initial D -->
            <div class="carousel-item">
                <img src="img/destacada3.jpg" alt="Camiseta Initial D">
                <div class="carousel-caption">
                    <h3>Camiseta Initial D</h3>
                    <p>Merchandising oficial inspirado en el legendario anime.</p>
                </div>
            </div>

            <!-- Toyota AE86 -->
            <div class="carousel-item">
                <img src="img/destacada4.jpg" alt="Toyota AE86 Trueno">
                <div class="carousel-caption">
                    <h3>Toyota AE86 Trueno</h3>
                    <p>El coche más icónico del drift y del mundo Initial D.</p>
                </div>
            </div>

            <!-- Volante Drift -->
            <div class="carousel-item">
                <img src="img/destacada5.jpg" alt="Volante Drift">
                <div class="carousel-caption">
                    <h3>Volante Drift</h3>
                    <p>Control total y agarre perfecto para conducción deportiva.</p>
                </div>
            </div>

        </div>



            <!-- Botones del carrusel -->
            <button class="carousel-btn prev" aria-label="Anterior">&#10094;</button>
            <button class="carousel-btn next" aria-label="Siguiente">&#10095;</button>

            <!-- Indicadores -->
            <div class="carousel-indicators">
                <span class="dot active" aria-label="Slide 1"></span>
                <span class="dot" aria-label="Slide 2"></span>
                <span class="dot" aria-label="Slide 3"></span>
                <span class="dot" aria-label="Slide 4"></span>
                <span class="dot" aria-label="Slide 5"></span>
            </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="about">
        <h2>Sobre nosotros</h2>

        <p>
            En <strong>Initial D</strong> combinamos pasión por la velocidad y calidad automotriz. 
            Encuentra desde autos clásicos hasta los accesorios más modernos. Garantizamos confianza, 
            rendimiento y estilo en cada producto.
        </p>

        <div class="about-grid">
            <div class="about-card">
                <i class="fa-solid fa-gear"></i>
                <h3>Especialistas</h3>
                <p>Equipo experto en mecánica japonesa.</p>
            </div>

            <div class="about-card">
                <i class="fa-solid fa-handshake"></i>
                <h3>Confianza</h3>
                <p>Más de 2.000 clientes satisfechos.</p>
            </div>

            <div class="about-card">
                <i class="fa-solid fa-star"></i>
                <h3>Calidad</h3>
                <p>Solo productos verificados y auténticos.</p>
            </div>
        </div>
    </section>

</main>

<!-- SCRIPT -->
<script src="js/script.js"></script>

<?php include("php/footer.php"); ?>
