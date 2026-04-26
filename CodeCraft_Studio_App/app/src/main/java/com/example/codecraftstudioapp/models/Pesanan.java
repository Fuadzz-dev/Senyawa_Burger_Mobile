package com.example.codecraftstudioapp.models;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class Pesanan {
    @SerializedName("id_pesanan")
    private int idPesanan;

    @SerializedName("nama")
    private String nama;

    @SerializedName("no_telepon")
    private String noTelepon;

    @SerializedName("email")
    private String email;

    @SerializedName("total_harga")
    private double totalHarga;

    @SerializedName("status_pembayaran")
    private String statusPembayaran;

    @SerializedName("payment_reference")
    private String paymentReference;

    @SerializedName("detail_pesanan")
    private List<DetailPesanan> detailPesanan;

    public int getIdPesanan() { return idPesanan; }
    public String getNama() { return nama; }
    public String getNoTelepon() { return noTelepon; }
    public String getEmail() { return email; }
    public double getTotalHarga() { return totalHarga; }
    public String getStatusPembayaran() { return statusPembayaran; }
    public String getPaymentReference() { return paymentReference; }
    public List<DetailPesanan> getDetailPesanan() { return detailPesanan; }
}
