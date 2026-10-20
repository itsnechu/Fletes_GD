function contexto(vistaInicial = 'home') {
    return {
        vista: vistaInicial,
        choferes: [],
        unidades: [],
        fletes: [],

        nuevo_chofer: {
            nombre: '',
            licencia: ''
        },

        nueva_unidad: {
            placa: '',
            marca: '',
            modelo: '',
            year: ''
        },

        async init() {
            await this.cargar_choferes();
            await this.cargar_unidades();
        },

        async cargar_choferes() {
            try {
                const res = await fetch('api_choferes.php');
                this.choferes = await res.json();
            } catch (e) {
                console.error("Error al cargar choferes:", e);
            }
        },

        async cargar_unidades() {
            try {
                const res = await fetch('api_unidades.php');
                this.unidades = await res.json();
            } catch (e) {
                console.error("Error al cargar unidades:", e);
            }
        },

        async agregar_chofer() {
            try {
                const res = await fetch('api_choferes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.nuevo_chofer)
                });

                if (res.ok) {
                    this.nuevo_chofer = { nombre: '', licencia: '' };
                    await this.cargar_choferes();
                    this.vista = 'choferes';
                }
            } catch (e) {
                console.error("Error al registrar chofer:", e);
            }
        },

        async agregar_unidad() {
            try {
                const res = await fetch('api_unidades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.nueva_unidad)
                });

                if (res.ok) {
                    this.nueva_unidad = { placa: '', marca: '', modelo: '', year: '' };
                    await this.cargar_unidades();
                    this.vista = 'unidades';
                }
            } catch (e) {
                console.error("Error al registrar unidad:", e);
            }
        },

        formato_dinero(cantidad) {
            return Number(cantidad).toLocaleString("es-MX", { style: "currency", currency: "MXN" });
        }
    };
}