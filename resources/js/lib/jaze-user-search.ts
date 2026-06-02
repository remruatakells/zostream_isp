import type { JazeUser } from "./jaze";

type SearchableField = {
    value: string;
    weight: number;
};

function normalizeSearchValue(value: string): string {
    return value.trim().toLowerCase();
}

function normalizePhoneValue(value: string): string {
    return value.replace(/\D/g, "");
}

function buildSearchableFields(user: JazeUser): SearchableField[] {
    return [
        { value: user.name ?? "", weight: 120 },
        { value: user.phone ?? "", weight: 110 },
        { value: user.email ?? "", weight: 100 },
        { value: user.username ?? "", weight: 95 },
        { value: user.id ?? "", weight: 115 },
        { value: user.account_id ?? "", weight: 90 },
    ].filter((field) => field.value.trim().length > 0);
}

function getFieldScore(query: string, field: SearchableField): number {
    const normalizedValue = normalizeSearchValue(field.value);

    if (!normalizedValue) {
        return 0;
    }

    if (normalizedValue === query) {
        return field.weight + 1000;
    }

    if (normalizedValue.startsWith(query)) {
        return field.weight + 700 - normalizedValue.length;
    }

    if (normalizedValue.includes(query)) {
        return field.weight + 500 - normalizedValue.indexOf(query);
    }

    const queryPhone = normalizePhoneValue(query);
    const valuePhone = normalizePhoneValue(field.value);

    if (queryPhone && valuePhone) {
        if (valuePhone === queryPhone) {
            return field.weight + 950;
        }

        if (valuePhone.startsWith(queryPhone)) {
            return field.weight + 650 - valuePhone.length;
        }

        if (valuePhone.includes(queryPhone)) {
            return field.weight + 450 - valuePhone.indexOf(queryPhone);
        }
    }

    const queryTokens = query.split(/\s+/).filter(Boolean);

    if (queryTokens.length > 1) {
        const allTokensMatch = queryTokens.every((token) =>
            normalizedValue.includes(token),
        );

        if (allTokensMatch) {
            return field.weight + 300;
        }
    }

    let sequentialMatches = 0;
    let queryIndex = 0;

    for (const character of normalizedValue) {
        if (character === query[queryIndex]) {
            sequentialMatches += 1;
            queryIndex += 1;

            if (queryIndex === query.length) {
                break;
            }
        }
    }

    if (query.length >= 3 && sequentialMatches === query.length) {
        return field.weight + 150;
    }

    return 0;
}

export function searchJazeUsers(users: JazeUser[], rawQuery: string): JazeUser[] {
    const query = normalizeSearchValue(rawQuery);

    if (!query) {
        return users;
    }

    return users
        .map((user, index) => {
            const bestScore = buildSearchableFields(user).reduce(
                (highestScore, field) =>
                    Math.max(highestScore, getFieldScore(query, field)),
                0,
            );

            return {
                user,
                bestScore,
                index,
            };
        })
        .filter((entry) => entry.bestScore > 0)
        .sort((left, right) => {
            if (right.bestScore !== left.bestScore) {
                return right.bestScore - left.bestScore;
            }

            return left.index - right.index;
        })
        .map((entry) => entry.user);
}
