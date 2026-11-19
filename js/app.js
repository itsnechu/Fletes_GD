function contexto(vistaInicial = 'home') {
    return {
        vista: vistaInicial,
        choferes: [],
        unidades: [],
        clientes: [],
        fletes: [],
        estatus_validos: ['en tránsito', 'entregado', 'pendiente de pago', 'pagado', 'cancelado'],

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

        nuevo_cliente: {
            razon_social: '',
            rfc: '',
            email: ''
        },

        nuevo_flete: {
            origen: '',
            destino: '',
            cliente_id: '',
            chofer_id: '',
            unidad_id: '',
            tarifa: ''
        },

        async init() {
            await this.cargar_choferes();
            await this.cargar_unidades();
            await this.cargar_clientes();
            await this.cargar_fletes();
        },

        async cargar_choferes() {
            try {
                const res = await fetch('api/api_choferes.php');
                this.choferes = await res.json();
            } catch (e) {
                console.error("Error al cargar choferes:", e);
            }
        },

        async cargar_unidades() {
            try {
                const res = await fetch('api/api_unidades.php');
                this.unidades = await res.json();
            } catch (e) {
                console.error("Error al cargar unidades:", e);
            }
        },

        async cargar_clientes() {
            try {
                const res = await fetch('api/api_clientes.php');
                this.clientes = await res.json();
            } catch (e) {
                console.error("Error al cargar clientes:", e);
            }
        },

        async cargar_fletes() {
            try {
                const res = await fetch('api/api_nuevoflete.php');
                this.fletes = await res.json();
            } catch (e) {
                console.error("Error al cargar fletes:", e);
            }
        },

        async agregar_chofer() {
            try {
                const res = await fetch('api/api_choferes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.nuevo_chofer)
                });

                if (res.ok) {
                    this.nuevo_chofer = { nombre: '', licencia: '' };
                    await this.cargar_choferes();
                }
            } catch (e) {
                console.error("Error al registrar chofer:", e);
            }
        },

        async agregar_unidad() {
            try {
                const res = await fetch('api/api_unidades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.nueva_unidad)
                });

                if (res.ok) {
                    this.nueva_unidad = { placa: '', marca: '', modelo: '', year: '' };
                    await this.cargar_unidades();
                }
            } catch (e) {
                console.error("Error al registrar unidad:", e);
            }
        },

        async agregar_cliente() {
            try {
                const res = await fetch('api/api_clientes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.nuevo_cliente)
                });

                if (res.ok) {
                    this.nuevo_cliente = { razon_social: '', rfc: '', email: '' };
                    await this.cargar_clientes();
                }
            } catch (e) {
                console.error("Error al registrar cliente:", e);
            }
        },

        async agregar_flete() {
            try {
                const res = await fetch('api/api_nuevoflete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.nuevo_flete)
                });

                if (res.ok) {
                    this.nuevo_flete = { origen: '', destino: '', cliente_id: '', chofer_id: '', unidad_id: '', tarifa: '' };
                    await this.cargar_fletes();
                }
            } catch (e) {
                console.error("Error al registrar flete:", e);
            }
        },

        async cambiar_estatus(id, nuevoEstatus) {
            try {
                const res = await fetch(`api/api_nuevoflete.php?accion=estatus&id=${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ estatus: nuevoEstatus })
                });

                if (res.ok) {
                    await this.cargar_fletes();
                }
            } catch (e) {
                console.error("Error al actualizar estatus:", e);
            }
        },

        async eliminar_flete(id) {
            if (!confirm("¿Estás seguro de eliminar este flete?")) return;
            try {
                const res = await fetch(`api/api_nuevoflete.php?id=${id}`, {
                    method: 'DELETE'
                });

                if (res.ok) {
                    await this.cargar_fletes();
                }
            } catch (e) {
                console.error("Error al eliminar flete:", e);
            }
        },

        formato_dinero(cantidad) {
            return Number(cantidad).toLocaleString("es-MX", { style: "currency", currency: "MXN" });
        }
    };
}