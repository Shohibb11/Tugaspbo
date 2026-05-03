package com.mycompany.uts;

public class UTS {
    public static void main(String[] args) {

        User user = new User(1, "user@mail.com", "12345");
        Penerbit penerbit = new Penerbit(1, "Gramedia", "Jakarta");
        Buku buku = new Buku(1, "Pemrograman Java", "Andi", 2024, penerbit);

        System.out.println("=== USER ===");
        user.index();
        user.store();
        user.update();
        user.destroy();

        System.out.println("\n=== PENERBIT ===");
        penerbit.index();
        penerbit.store();
        penerbit.update();
        penerbit.destroy();

        System.out.println("\n=== BUKU ===");
        buku.index();
        buku.store();
        buku.update();
        buku.destroy();
    }
}