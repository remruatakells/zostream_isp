import { useState } from "react";
import { AllUserAddForm } from "./all-user-add-form";
import { AllUserManageSearch } from "./all-user-manage-search";
import { AllUserSubscriptionForm } from "./all-user-subscription-form";

type SubscriberTab = "add" | "subscribe" | "manage";

const subscriberTabs: { id: SubscriberTab; label: string }[] = [
    { id: "add", label: "Add New Subscriber" },
    { id: "subscribe", label: "Current Subscriber" },
    { id: "manage", label: "Manage Subscriber" },
];

export function AllUserPage() {
    const [activeTab, setActiveTab] = useState<SubscriberTab>("add");

    return (
        <section className="flex flex-1 flex-col items-start gap-4 py-8 lg:py-12">
            <div className="mx-auto grid w-full grid-cols-3 rounded-2xl border border-[var(--border-subtle)] bg-[var(--panel-soft)] p-1 sm:inline-grid sm:w-auto">
                {subscriberTabs.map((tab) => (
                    <button
                        key={tab.id}
                        type="button"
                        onClick={() => setActiveTab(tab.id)}
                        className={`h-11 rounded-xl px-2 text-center text-[11px] font-semibold leading-tight transition sm:h-12 sm:px-5 sm:text-sm sm:leading-normal ${
                            activeTab === tab.id
                                ? "bg-[var(--accent)] text-[var(--accent-foreground)] shadow-[0_12px_28px_rgba(14,165,164,0.24)]"
                                : "text-[var(--muted-foreground)] hover:text-[var(--foreground)]"
                        }`}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="w-full rounded-[2rem] border border-[var(--border-subtle)] bg-[var(--panel)] p-6 shadow-[0_24px_90px_rgba(15,23,42,0.18)] backdrop-blur xl:p-8">
                <p className="text-sm font-semibold uppercase tracking-[0.34em] text-[var(--accent-strong)]">
                    Subscriber
                </p>
                <h1 className="mt-5 text-4xl font-semibold leading-tight tracking-tight text-[var(--foreground)]">
                    Subscriber workspace
                </h1>
                <p className="mt-4 max-w-2xl text-base leading-7 text-[var(--muted-foreground)]">
                    Subscriber tools live here: new WIFI registration, Zo Stream
                    subscription, and saved subscriber management.
                </p>

                <div className="mt-6 py-5">
                    {activeTab === "add" ? <AllUserAddForm /> : null}
                    {activeTab === "subscribe" ? (
                        <AllUserSubscriptionForm />
                    ) : null}
                    {activeTab === "manage" ? <AllUserManageSearch /> : null}
                </div>
            </div>
        </section>
    );
}
