function SkeletonBlock({
    className,
}: {
    className: string;
}) {
    return (
        <div
            className={`animate-pulse bg-[var(--panel-soft)] ${className}`}
        />
    );
}

export function DashboardSkeleton() {
    return (
        <>
            <div className="grid gap-5 lg:grid-cols-[260px_minmax(0,1fr)] lg:items-center">
                <div className="mx-auto flex w-full max-w-[260px] flex-col items-center justify-center">
                    <div className="min-h-10 w-full">
                        <SkeletonBlock className="mx-auto h-4 w-40 rounded-full" />
                    </div>

                    <div className="flex h-[220px] w-[220px] items-center justify-center rounded-full border border-[var(--border-subtle)] bg-[var(--panel-soft)]">
                        <div className="flex h-28 w-28 items-center justify-center rounded-full bg-[var(--panel)]">
                            <SkeletonBlock className="h-8 w-12 rounded-full" />
                        </div>
                    </div>
                </div>

                <div className="grid gap-3">
                    <SkeletonBlock className="h-[54px] rounded-4xl" />
                    <SkeletonBlock className="h-[54px] rounded-4xl" />
                    <SkeletonBlock className="h-[54px] rounded-4xl" />
                    <SkeletonBlock className="h-[54px] rounded-4xl" />
                </div>
            </div>

            <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
                <SkeletonBlock className="h-[108px] rounded-[1.5rem]" />
            </div>
        </>
    );
}
