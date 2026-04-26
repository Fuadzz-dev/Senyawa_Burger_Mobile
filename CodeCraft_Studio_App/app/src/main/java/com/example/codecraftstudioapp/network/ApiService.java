package com.example.codecraftstudioapp.network;

import com.example.codecraftstudioapp.models.CheckoutRequest;
import com.example.codecraftstudioapp.models.CheckoutResponse;
import com.example.codecraftstudioapp.models.DetailMenuResponse;
import com.example.codecraftstudioapp.models.MenuResponse;
import com.example.codecraftstudioapp.models.PesananResponse;
import com.example.codecraftstudioapp.models.StatusResponse;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.GET;
import retrofit2.http.POST;
import retrofit2.http.Path;

public interface ApiService {
    @GET("api/menus")
    Call<MenuResponse> getMenus();

    @GET("api/menus/{id}")
    Call<DetailMenuResponse> getDetailMenu(@Path("id") int idMenu);

    @POST("api/pembayaran/checkout")
    Call<CheckoutResponse> processCheckout(@Body CheckoutRequest request);

    @GET("api/pesanan/{id}")
    Call<PesananResponse> getPesananDetail(@Path("id") int idPesanan);

    @GET("api/pembayaran/status/{reference}")
    Call<StatusResponse> checkQrisStatus(@Path("reference") String reference);

    @GET("api/pesanan/status/{id}")
    Call<StatusResponse> checkKasirStatus(@Path("id") int idPesanan);
}
