import { useEffect, useState } from "react";
import { getMenus, checkoutPesanan } from "./customerApi";

export default function CustomerApp() {
    const [menus, setMenus] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function loadMenus() {
            try {
                const response = await getMenus();
                setMenus(response.data || []);
            } catch (error) {
                console.error(error);
            } finally {
                setLoading(false);
            }
        }

        loadMenus();
    }, []);

    const handleCheckout = async () => {
        const payload = {
            nama: "Budi Santoso",
            phone: "081234567890",
            email: "budi@email.com",
            orderType: "dine_in",
            paymentMethod: "online",
            amount: 45000,
            catatan: "Tanpa sambal",
            cart: [
                { id: 1, qty: 2, price: 20000, notes: "Pedas" },
                { id: 3, qty: 1, price: 5000, note: "Tanpa saos" },
            ],
        };

        try {
            const response = await checkoutPesanan(payload);
            console.log("Checkout success:", response);
            alert(
                "Pembayaran berhasil dibuat. Cek QR / redirect sesuai kebutuhan Anda.",
            );
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    };

    if (loading) {
        return <div>Loading menu...</div>;
    }

    return (
        <div>
            <h1>Menu Pelanggan</h1>
            {menus.map((menu) => (
                <div key={menu.id_menu} style={{ marginBottom: 12 }}>
                    <h3>{menu.nama_menu}</h3>
                    <p>Rp {Number(menu.harga).toLocaleString("id-ID")}</p>
                    <small>{menu.Kategori}</small>
                </div>
            ))}

            <button onClick={handleCheckout}>Checkout</button>
        </div>
    );
}
