const API_URL = import.meta.env.VITE_API_URL || "http://localhost:8000/api";

export async function getMenus() {
    const response = await fetch(`${API_URL}/customer/menus`, {
        headers: { Accept: "application/json" },
    });

    if (!response.ok) {
        throw new Error("Gagal mengambil daftar menu");
    }

    return response.json();
}

export async function getMenuDetail(id) {
    const response = await fetch(`${API_URL}/customer/menus/${id}`, {
        headers: { Accept: "application/json" },
    });

    if (!response.ok) {
        throw new Error("Gagal mengambil detail menu");
    }

    return response.json();
}

export async function checkoutPesanan(payload) {
    const response = await fetch(`${API_URL}/customer/checkout`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || "Checkout gagal");
    }

    return data;
}

export async function getOrderStatus(id) {
    const response = await fetch(`${API_URL}/customer/orders/${id}`, {
        headers: { Accept: "application/json" },
    });

    if (!response.ok) {
        throw new Error("Gagal mengambil status pesanan");
    }

    return response.json();
}
