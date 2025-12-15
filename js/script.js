/* 
   INITIAL D - SCRIPT.JS PRINCIPAL
   Archivo centralizado de funcionalidades JavaScript
 */


/*  1. INICIALIZACIÓN PRINCIPAL */
document.addEventListener("DOMContentLoaded", () => {
    initCarousel();
    initThemeToggle();
    initMensajeContainer();
    initLoginPassword();
    initRegistroPassword();
    initProductosEstrellas();
    initGaleriaImagenes();
    initHeaderBurger();
    initCheckoutPago();
    initBizumOTP();
    initToasts();
    initScrollPosition();
    initDetectarBanco();
});

/* 2. CAROUSEL - Página principal */
function initCarousel() {
    const carousel = document.querySelector(".carousel");
    if (!carousel) return;

    const track = carousel.querySelector(".carousel-track");
    const items = Array.from(carousel.querySelectorAll(".carousel-item"));
    const prevBtn = carousel.querySelector(".carousel-btn.prev");
    const nextBtn = carousel.querySelector(".carousel-btn.next");
    const dots = Array.from(carousel.querySelectorAll(".dot"));

    let currentIndex = items.findIndex(item => item.classList.contains("active"));
    if (currentIndex < 0) currentIndex = 0;

    let isAnimating = false;
    let autoplayTimer = null;
    let userInteracted = false;
    const autoplayDelay = 5000;
    const swipeThreshold = 50;

    function updateCarousel(index) {
        if (isAnimating) return;
        isAnimating = true;

        if (index < 0) index = items.length - 1;
        if (index >= items.length) index = 0;

        currentIndex = index;
        track.style.transform = `translateX(-${currentIndex * 100}%)`;

        items.forEach(i => i.classList.remove("active"));
        items[currentIndex].classList.add("active");

        if (dots.length) {
            dots.forEach(dot => dot.classList.remove("active"));
            dots[currentIndex].classList.add("active");
        }

        setTimeout(() => { isAnimating = false; }, 650);
    }

    function startAutoplay() {
        if (autoplayTimer) return;
        autoplayTimer = setInterval(() => {
            updateCarousel(currentIndex + 1);
        }, autoplayDelay);
    }

    function stopAutoplay() {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }

    function restartAutoplay() {
        userInteracted = true;
        stopAutoplay();
        startAutoplay();
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", () => {
            updateCarousel(currentIndex - 1);
            restartAutoplay();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", () => {
            updateCarousel(currentIndex + 1);
            restartAutoplay();
        });
    }

    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
            updateCarousel(index);
            restartAutoplay();
        });
    });

    carousel.addEventListener("mouseenter", () => {
        if (userInteracted) stopAutoplay();
    });

    carousel.addEventListener("mouseleave", () => {
        if (userInteracted) startAutoplay();
    });

    carousel.setAttribute("tabindex", "0");
    carousel.addEventListener("keydown", (e) => {
        if (e.key === "ArrowLeft") {
            updateCarousel(currentIndex - 1);
            restartAutoplay();
        }
        if (e.key === "ArrowRight") {
            updateCarousel(currentIndex + 1);
            restartAutoplay();
        }
    });

    let startX = 0, currentX = 0, swiping = false;

    track.addEventListener("touchstart", (e) => {
        startX = e.touches[0].clientX;
        currentX = startX;
        swiping = true;
    }, { passive: true });

    track.addEventListener("touchmove", (e) => {
        if (!swiping) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });

    track.addEventListener("touchend", () => {
        if (!swiping) return;
        const diff = currentX - startX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff < 0) updateCarousel(currentIndex + 1);
            else updateCarousel(currentIndex - 1);
            restartAutoplay();
        }
        swiping = false;
    });

    updateCarousel(currentIndex);
    setTimeout(startAutoplay, 600);
}

/*  3. THEME TOGGLE - Sistema de temas claro/oscuro */
function initThemeToggle() {
    const themeToggle = document.querySelector('#theme-toggle');
    if (!themeToggle) return;

    const themeIcon = themeToggle.querySelector('i');
    const stored = localStorage.getItem('site-theme');

    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark');
            if (themeIcon) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        } else {
            document.body.classList.remove('dark');
            if (themeIcon) {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }
        localStorage.setItem('site-theme', theme);
    }

    // Aplicar tema guardado o detectar preferencia del sistema
    if (stored) {
        applyTheme(stored);
    } else {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(prefersDark ? 'dark' : 'light');
    }

    // Toggle al hacer click
    themeToggle.addEventListener('click', () => {
        const isDark = document.body.classList.contains('dark');
        applyTheme(isDark ? 'light' : 'dark');
    });
}

/* 4. MENSAJE CONTAINER - Mensajes con redirección */
function initMensajeContainer() {
    const mensajeContainer = document.querySelector('.mensaje-container');
    if (!mensajeContainer) return;

    const mensaje = mensajeContainer.querySelector('.mensaje');
    if (!mensaje) return;

    setTimeout(() => { mensaje.style.opacity = '0'; }, 4000);

    const url = mensajeContainer.getAttribute('data-redirigir');
    if (url) {
        setTimeout(() => { window.location.href = url; }, 4000);
    }
}

/* 5. LOGIN - Toggle password */
function initLoginPassword() {
    const toggleBtn = document.getElementById("togglePass");
    if (!toggleBtn) return;

    toggleBtn.addEventListener("click", () => {
        const pass = document.getElementById("password");
        const eyeOpen = document.getElementById("eyeOpen");
        const eyeClosed = document.getElementById("eyeClosed");

        if (!pass || !eyeOpen || !eyeClosed) return;

        if (pass.type === "password") {
            pass.type = "text";
            eyeOpen.style.display = "none";
            eyeClosed.style.display = "block";
        } else {
            pass.type = "password";
            eyeOpen.style.display = "block";
            eyeClosed.style.display = "none";
        }
    });
}

/*  6. REGISTRO - Validación de contraseñas */
function initRegistroPassword() {
    function togglePasswordRegistro(inputId, eyeOpenId, eyeClosedId) {
        const input = document.getElementById(inputId);
        const eyeOpen = document.getElementById(eyeOpenId);
        const eyeClosed = document.getElementById(eyeClosedId);

        if (!input || !eyeOpen || !eyeClosed) return;

        if (input.type === "password") {
            input.type = "text";
            eyeOpen.style.display = "none";
            eyeClosed.style.display = "block";
        } else {
            input.type = "password";
            eyeOpen.style.display = "block";
            eyeClosed.style.display = "none";
        }
    }

    const togglePass1 = document.getElementById("togglePass1");
    const togglePass2 = document.getElementById("togglePass2");

    if (togglePass1) {
        togglePass1.addEventListener("click", () => {
            togglePasswordRegistro("password", "eyeOpen1", "eyeClosed1");
        });
    }

    if (togglePass2) {
        togglePass2.addEventListener("click", () => {
            togglePasswordRegistro("password2", "eyeOpen2", "eyeClosed2");
        });
    }

    const strengthBar = document.getElementById("strengthBar");
    const strengthText = document.getElementById("strengthText");
    const pass1 = document.getElementById("password");
    const pass2 = document.getElementById("password2");
    const passError = document.getElementById("passError");

    if (pass1 && strengthBar && strengthText) {
        pass1.addEventListener("input", () => {
            const val = pass1.value;
            let score = 0;

            if (val.length >= 6) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const colors = ["#ff3b30", "#ff9500", "#ffcc00", "#34c759", "#30d158"];
            const text = ["Muy débil", "Débil", "Regular", "Fuerte", "Muy fuerte"];

            strengthBar.style.width = ((score / 5) * 100) + "%";
            strengthBar.style.background = colors[score - 1] || "#ddd";
            strengthText.textContent = text[score - 1] || "";
        });
    }

    if (pass2 && pass1 && passError) {
        pass2.addEventListener("input", () => {
            if (pass1.value !== pass2.value) {
                passError.style.display = "block";
                pass2.style.borderColor = "red";
            } else {
                passError.style.display = "none";
                pass2.style.borderColor = "green";
            }
        });
    }

    const formRegistro = document.getElementById("formRegistro");
    if (formRegistro && pass1 && pass2 && passError) {
        formRegistro.addEventListener("submit", (e) => {
            if (pass1.value !== pass2.value) {
                e.preventDefault();
                passError.style.display = "block";
            }
        });
    }
}

/*  7. PRODUCTOS - Búsqueda y filtros */
let timerBuscar = null;

function autoBuscar() {
    clearTimeout(timerBuscar);
    timerBuscar = setTimeout(() => {
        const form = document.querySelector(".catalogo-filtros");
        if (form) form.submit();
    }, 500);
}

function autoOrden() {
    const form = document.querySelector(".catalogo-filtros");
    if (form) form.submit();
}

function cancelarFiltros() {
    window.location.href = "productos.php";
}

/*  8. ESTRELLAS INTERACTIVAS - Producto detalle */
function initProductosEstrellas() {
    const ratingBox = document.querySelector(".rating-input");
    if (!ratingBox) return;

    const estrellas = ratingBox.querySelectorAll(".star-input");
    const inputValor = document.getElementById("puntuacion");
    if (!inputValor) return;

    let valorGuardado = parseInt(ratingBox.dataset.valor) || 0;

    function pintar(valor) {
        estrellas.forEach(star => {
            const v = parseInt(star.dataset.value);
            star.classList.toggle("active", v <= valor);
        });
    }

    pintar(valorGuardado);

    estrellas.forEach(star => {
        star.addEventListener("mouseover", () => {
            const val = parseInt(star.dataset.value);
            estrellas.forEach(s => {
                const v = parseInt(s.dataset.value);
                s.classList.toggle("hover", v <= val);
            });
        });

        star.addEventListener("mouseout", () => {
            estrellas.forEach(s => s.classList.remove("hover"));
        });

        star.addEventListener("click", () => {
            valorGuardado = parseInt(star.dataset.value);
            inputValor.value = valorGuardado;
            pintar(valorGuardado);
        });
    });
}

/*  9. GALERÍA DE IMÁGENES - Producto detalle */
function initGaleriaImagenes() {
    const miniaturas = document.querySelectorAll(".miniatura");
    const imagenPrincipal = document.getElementById("imagenPrincipal");

    if (!imagenPrincipal || miniaturas.length === 0) return;

    let algunaActiva = false;
    miniaturas.forEach(m => {
        if (m.classList.contains("active")) algunaActiva = true;
    });

    if (!algunaActiva && miniaturas[0]) {
        miniaturas[0].classList.add("active");
        imagenPrincipal.src = miniaturas[0].dataset.full;
    }

    miniaturas.forEach(mini => {
        mini.addEventListener("click", () => {
            miniaturas.forEach(m => m.classList.remove("active"));
            mini.classList.add("active");

            imagenPrincipal.style.opacity = 0;
            setTimeout(() => {
                imagenPrincipal.src = mini.dataset.full;
                imagenPrincipal.style.opacity = 1;
            }, 200);
        });
    });
}

/* 10. HEADER - Menú burger y scroll */
function initHeaderBurger() {
    const burger = document.querySelector('.burger');
    const nav = document.querySelector('.nav-links');

    if (burger && nav) {
        burger.addEventListener('click', () => {
            burger.classList.toggle("active");
            nav.classList.toggle('active');
        });
    }

    window.addEventListener("scroll", () => {
        const header = document.querySelector("header");
        if (header) {
            header.classList.toggle("scrolled", window.scrollY > 10);
        }
    });

    const userDropdown = document.querySelector('.user-dropdown');
    const userTrigger = document.querySelector('.user-trigger');

    if (userDropdown && userTrigger) {
        userTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        document.addEventListener('click', () => {
            userDropdown.classList.remove('active');
        });
    }
}

/* 11. CHECKOUT - Métodos de pago */
function initCheckoutPago() {
    const radios = document.querySelectorAll("input[name='metodo_pago']");
    if (radios.length === 0) return;

    const tarjeta = document.getElementById("tarjetaDatos");
    const paypal = document.getElementById("paypalDatos");
    const bizum = document.getElementById("bizumDatos");
    const trans = document.getElementById("transferenciaInfo");

    const overlay = document.getElementById("overlay-pago");
    const btn = document.getElementById("btnConfirmarPago");
    const form = document.getElementById("formPago");

    function actualizar() {
        [tarjeta, paypal, bizum, trans].forEach(b => b && b.classList.remove("visible"));

        const sel = document.querySelector("input[name='metodo_pago']:checked");
        if (!sel) return;

        if (sel.value === "tarjeta" && tarjeta) tarjeta.classList.add("visible");
        if (sel.value === "paypal" && paypal) paypal.classList.add("visible");
        if (sel.value === "bizum" && bizum) bizum.classList.add("visible");
        if (sel.value === "transferencia" && trans) trans.classList.add("visible");
    }

    radios.forEach(r => r.addEventListener("change", actualizar));
    actualizar();

    if (btn && form && overlay) {
        btn.addEventListener("click", () => {
            overlay.style.display = "flex";
            setTimeout(() => form.submit(), 2000);
        });
    }
}

/* 12. BIZUM - Código OTP */
function initBizumOTP() {
    const btnBizum = document.getElementById("btnEnviarCodigoBizum");
    const btnReenviar = document.getElementById("btnReenviarBizum");
    const aviso = document.getElementById("bizumAviso");
    const boxCodigo = document.getElementById("bizumCodigoBox");

    if (!btnBizum || !aviso) return;

    let counter = 90;
    let timer = null;

    function iniciarCuentaAtras() {
        counter = 90;
        btnBizum.disabled = true;
        if (btnReenviar) btnReenviar.style.display = "none";

        aviso.style.display = "block";
        aviso.style.color = "#ffcf00";
        aviso.innerHTML = `Código enviado. Revisa tu correo.<br>
                           <small>Podrás reenviar en <strong id="bizumTimer">90</strong> s</small>`;

        timer = setInterval(() => {
            counter--;
            const timerEl = document.getElementById("bizumTimer");
            if (timerEl) timerEl.innerText = counter;

            if (counter <= 0) {
                clearInterval(timer);
                aviso.style.color = "#ffcf00";
                aviso.innerHTML = `Tiempo agotado. Puedes reenviar el código.`;
                btnBizum.disabled = false;
                if (btnReenviar) btnReenviar.style.display = "inline-block";
            }
        }, 1000);
    }

    function enviarCodigo() {
        const tel = document.getElementById("bizum_tel");
        if (!tel) return;

        aviso.style.display = "block";
        aviso.style.color = "#ffcf00";
        aviso.innerText = "Enviando código...";

        fetch("bizum_otp.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "telefono=" + tel.value.trim()
        })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    if (boxCodigo) boxCodigo.style.display = "block";
                    iniciarCuentaAtras();
                } else {
                    aviso.style.color = "red";
                    aviso.innerText = data.msg;
                }
            })
            .catch(() => {
                aviso.style.color = "red";
                aviso.innerText = "Error al conectar con el servidor.";
            });
    }

    btnBizum.addEventListener("click", enviarCodigo);
    if (btnReenviar) btnReenviar.addEventListener("click", enviarCodigo);
}

/*  13. TOASTS - Notificaciones animadas */
function initToasts() {
    // Toast de productos con animación completa
    const toast = document.getElementById('toast');
    if (toast) {
        const isMobile = window.innerWidth <= 600;

        toast.style.opacity = '0';
        if (isMobile) {
            toast.style.transform = 'translateX(-50%) translateY(-20px)';
        } else {
            toast.style.transform = 'translateY(-20px)';
        }

        setTimeout(() => {
            toast.style.opacity = '1';
            if (isMobile) {
                toast.style.transform = 'translateX(-50%) translateY(0)';
            } else {
                toast.style.transform = 'translateY(0)';
            }
        }, 100);

        setTimeout(() => {
            toast.style.opacity = '0';
            if (isMobile) {
                toast.style.transform = 'translateX(-50%) translateY(-20px)';
            } else {
                toast.style.transform = 'translateY(-20px)';
            }
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // Toast de usuario panel
    const toastPanel = document.querySelector(".toast");
    if (toastPanel && !toast) {
        const isMobile = window.innerWidth <= 600;
        
        // Establecer estado inicial según dispositivo
        if (isMobile) {
            toastPanel.style.transform = 'translateX(-50%) translateY(-20px)';
        } else {
            toastPanel.style.transform = 'translateY(-20px)';
        }
        
        setTimeout(() => { 
            toastPanel.classList.add("show");
            // Aplicar transform correcto al mostrar
            if (isMobile) {
                toastPanel.style.transform = 'translateX(-50%) translateY(0)';
            } else {
                toastPanel.style.transform = 'translateY(0)';
            }
        }, 150);
        
        setTimeout(() => { 
            toastPanel.classList.remove("show");
            // Aplicar transform correcto al ocultar
            if (isMobile) {
                toastPanel.style.transform = 'translateX(-50%) translateY(-20px)';
            } else {
                toastPanel.style.transform = 'translateY(-20px)';
            }
        }, 3500);
    }
}

/*  14. SCROLL POSICION - Restaurar posición */
function initScrollPosition() {
    const scrollPos = sessionStorage.getItem('scrollPosition');
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        sessionStorage.removeItem('scrollPosition');
    }
}

function guardarPosicionScroll() {
    sessionStorage.setItem('scrollPosition', window.scrollY);
}

/* 15. DETECTAR BANCO - IBAN transferencia bancaria */
function initDetectarBanco() {
    const input = document.getElementById('ibanInput');
    if (!input) return;

    input.addEventListener('input', detectarBanco);
}

function detectarBanco() {
    const input = document.getElementById('ibanInput');
    const bancoDiv = document.getElementById('bancoDetectado');
    const nombreBanco = document.getElementById('nombreBanco');
    const bicSwift = document.getElementById('bicSwift');
    
    if (!input || !bancoDiv || !nombreBanco || !bicSwift) return;
    
    let iban = input.value.toUpperCase().replace(/\s/g, '');
    
    // Formatear con espacios cada 4 caracteres mientras escribe
    input.value = iban.match(/.{1,4}/g)?.join(' ') || iban;
    
    // Validar que sea IBAN español y tenga al menos los primeros caracteres
    if (!iban.startsWith('ES') || iban.length < 8) {
        bancoDiv.style.display = 'none';
        return;
    }
    
    // Extraer código del banco (posiciones 4-7)
    const codigoBanco = iban.substring(4, 8);
    
    // Base de datos de bancos españoles principales
    const bancos = {
        '0049': { nombre: 'Banco Santander', bic: 'BSCHESMMXXX' },
        '0182': { nombre: 'BBVA', bic: 'BBVAESMMXXX' },
        '2100': { nombre: 'CaixaBank', bic: 'CAIXESBBXXX' },
        '2103': { nombre: 'Unicaja Banco', bic: 'UCJAES2MXXX' },
        '0128': { nombre: 'Bankinter', bic: 'BKBKESMMXXX' },
        '0081': { nombre: 'Banco Sabadell', bic: 'BSABESBBXXX' },
        '0030': { nombre: 'Banco Español de Crédito', bic: 'BCOEESMM XXX' },
        '0061': { nombre: 'Banca March', bic: 'BMARES2MXXX' },
        '0075': { nombre: 'Banco Popular', bic: 'POPUESMMXXX' },
        '2038': { nombre: 'Bankia', bic: 'CAHMESMMXXX' },
        '0487': { nombre: 'Banco Mare Nostrum', bic: 'GBMNESMMXXX' },
        '0073': { nombre: 'Open Bank', bic: 'OPENESMMXXX' },
        '0019': { nombre: 'Deutsche Bank', bic: 'DEUTESBBXXX' },
        '0108': { nombre: 'Citibank', bic: 'CITIESMMXXX' },
        '2095': { nombre: 'Kutxabank', bic: 'BASKES2BXXX' },
        '0086': { nombre: 'Banco BNP Paribas', bic: 'BNPAESMMXXX' },
        '2085': { nombre: 'Ibercaja', bic: 'CAZRES2ZXXX' },
        '1465': { nombre: 'ING Direct', bic: 'INGDESMMXXX' },
        '0239': { nombre: 'EVO Banco', bic: 'EVOBESMMXXX' },
        '3035': { nombre: 'Caja Laboral', bic: 'CLPEES2MXXX' },
        '3058': { nombre: 'Cajamar', bic: 'CCRIES2AXXX' }
    };
    
    if (bancos[codigoBanco]) {
        nombreBanco.textContent = bancos[codigoBanco].nombre;
        bicSwift.textContent = bancos[codigoBanco].bic;
        bancoDiv.style.display = 'block';
    } else {
        nombreBanco.textContent = 'Banco no identificado (código: ' + codigoBanco + ')';
        bicSwift.textContent = 'No disponible';
        bancoDiv.style.display = 'block';
    }
}

/* 16. FUNCIONES GLOBALES - Usuario panel y admin */

// Toggle password (usuario panel)
function togglePassword(id, el) {
    const input = document.getElementById(id);
    const icon = el ? el.querySelector("i") : null;
    if (!input || !icon) return;

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

// Modal de confirmación
function confirmarAccion(url, titulo = "¿Estás seguro?", texto = "Esta acción no se puede deshacer.") {
    const modalTitle = document.getElementById("modalTitle");
    const modalText = document.getElementById("modalText");
    const modalAceptar = document.getElementById("modalAceptar");
    const modalConfirm = document.getElementById("modalConfirm");

    if (!modalTitle || !modalText || !modalAceptar || !modalConfirm) return;

    modalTitle.textContent = titulo;
    modalText.textContent = texto;
    modalAceptar.setAttribute("href", url);
    modalConfirm.classList.remove("hidden");
}

function closeModal() {
    const modalConfirm = document.getElementById("modalConfirm");
    if (modalConfirm) modalConfirm.classList.add("hidden");
}

// Toggle oferta (admin)
function toggleOferta() {
    const chk = document.querySelector("input[name='oferta']");
    const precioOferta = document.getElementById("precio_oferta");
    if (chk && precioOferta) {
        precioOferta.style.display = chk.checked ? "block" : "none";
    }
}

// Modal eliminar opinión (producto detalle)
function mostrarModalEliminar() {
    const modal = document.getElementById('modalEliminarOpinion');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalEliminar() {
    const modal = document.getElementById('modalEliminarOpinion');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Listener para cerrar modal con ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalEliminarOpinion');
        if (modal && modal.style.display === 'flex') {
            cerrarModalEliminar();
        }
    }
});

// Admin - Confirmar eliminar producto (SweetAlert2)
function confirmarEliminar(id) {
    if (typeof Swal === 'undefined') {
        if (confirm('¿Eliminar producto? Esta acción no se puede deshacer')) {
            window.location.href = 'productos_admin.php?eliminar=' + id;
        }
        return;
    }

    Swal.fire({
        title: '¿Eliminar producto?',
        html: 'Esta acción <strong>no se puede deshacer</strong>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'productos_admin.php?eliminar=' + id;
        }
    });
}
