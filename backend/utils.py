def suggest_lead_price(room_price: int) -> int:
    if room_price < 1_000_000:
        return 7000
    if room_price < 2_000_000:
        return 10000
    if room_price < 3_000_000:
        return 17000
    if room_price < 5_000_000:
        return 22000
    return 25000
